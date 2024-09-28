<?php

namespace Arakne\Spinneret\Runner;

use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\View\ViewEngineInterface;
use Closure;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function get_class;

/**
 * Default implementation of the RunnerInterface
 * Will handle all steps and PSR-15 middlewares
 */
final readonly class Runner implements RunnerInterface
{
    private RequestHandlerInterface $requestHandler;

    /**
     * @param list<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private RouterInterface $router,
        private PresenterDispatcherInterface $presenterDispatcher,
        private ViewEngineInterface $view,
        array $middlewares = [],
        private ?LoggerInterface $logger = null,
    ) {
        $this->requestHandler = $this->buildMiddlewareStack($middlewares);
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger?->info('Handling request {{ method }} {{ uri }} from {{ client }}', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'client' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'headers' => $request->getHeaders(),
        ]);

        try {
            $response = $this->requestHandler->handle($request);

            $this->logger?->info('Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'code' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
                'headers' => $response->getHeaders(),
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->logger?->error('Error occurs on middleware step for request {{ method }} {{ uri }} : {{ exception }}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'exception' => $e,
            ]);

            return $this->handleRoutedRequest(
                new RoutedRequest(
                    $request,
                    new InternalServerError(
                        RunnerStepEnum::Middleware,
                        $e,
                        $request,
                    ),
                    false
                ),
                catch: false,
            );
        }
    }

    #[Override]
    public function handleRoutedRequest(RoutedRequest $routedRequest, bool $catch = true): ResponseInterface
    {
        try {
            $responseDto = $this->presenterDispatcher->dispatch($routedRequest);
        } catch (Throwable $e) {
            $this->logger?->error('Error occurs on presenter step for request {{ method }} {{ uri }} : {{ exception }}', [
                'method' => $routedRequest->psrRequest->getMethod(),
                'uri' => $routedRequest->psrRequest->getUri(),
                'exception' => $e,
            ]);

            if (!$catch) {
                throw $e;
            }

            return $this->handleRoutedRequest(
                new RoutedRequest(
                    $routedRequest->psrRequest,
                    new InternalServerError(
                        RunnerStepEnum::Presenter,
                        $e,
                        $routedRequest->psrRequest,
                        $routedRequest->routedRequest,
                    ),
                    false
                ),
                catch: false,
            );
        }

        $this->logger?->debug('Response DTO {{ dto }} was generated', ['dto' => get_class($responseDto)]);

        try {
            return $this->view->response($responseDto, $routedRequest->psrRequest, $routedRequest->routedRequest);
        } catch (Throwable $e) {
            $this->logger?->error('Error occurs on view step for request {{ method }} {{ uri }} : {{ exception }}', [
                'method' => $routedRequest->psrRequest->getMethod(),
                'uri' => $routedRequest->psrRequest->getUri(),
                'exception' => $e,
            ]);

            if (!$catch) {
                throw $e;
            }

            return $this->handleRoutedRequest(
                new RoutedRequest(
                    $routedRequest->psrRequest,
                    new InternalServerError(
                        RunnerStepEnum::View,
                        $e,
                        $routedRequest->psrRequest,
                        $routedRequest->routedRequest,
                        $responseDto,
                    ),
                    false
                ),
                catch: false,
            );
        }
    }

    private function handleServerRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $routedRequest = $this->router->request($request);
        } catch (Throwable $e) {
            $this->logger?->error('Error occurs on router step for request {{ method }} {{ uri }} : {{ exception }}', [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'exception' => $e,
            ]);

            return $this->handleRoutedRequest(
                new RoutedRequest(
                    $request,
                    new InternalServerError(RunnerStepEnum::Router, $e, $request),
                    false
                ),
                catch: false,
            );
        }

        $this->logger?->debug('Request {{ method }} {{ uri }} was routed to {{ target }}', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'routedRequest' => $routedRequest->routedRequest,
            'target' => get_class($routedRequest->routedRequest),
        ]);

        return $this->handleRoutedRequest($routedRequest);
    }

    /**
     * Build the middleware stack to create the request handler
     *
     * @param list<MiddlewareInterface> $middlewares
     * @return RequestHandlerInterface
     *
     * @see Runner::handleServerRequest() Will be used as outlet handler
     */
    private function buildMiddlewareStack(array $middlewares): RequestHandlerInterface
    {
        $logger = $this->logger;
        $requestHandler = new readonly class ($this->handleServerRequest(...)) implements RequestHandlerInterface {
            /**
             * @param Closure(ServerRequestInterface):ResponseInterface $handler
             */
            public function __construct(private Closure $handler)
            {
            }

            #[Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handler)($request);
            }
        };

        foreach ($middlewares as $middleware) {
            $requestHandler = new readonly class ($middleware, $requestHandler, $logger) implements RequestHandlerInterface {
                public function __construct(
                    private MiddlewareInterface $middleware,
                    private RequestHandlerInterface $next,
                    private ?LoggerInterface $logger,
                ) {
                }

                #[Override]
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $this->logger?->debug('Start Middleware {{ middleware }}', ['middleware' => get_class($this->middleware)]);

                    try {
                        return $this->middleware->process($request, $this->next);
                    } finally {
                        $this->logger?->debug('End Middleware {{ middleware }}', ['middleware' => get_class($this->middleware)]);
                    }
                }
            };
        }

        return $requestHandler;
    }
}
