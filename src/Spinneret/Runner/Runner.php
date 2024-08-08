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
use Throwable;

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
    ) {
        $this->requestHandler = $this->buildMiddlewareStack($middlewares);
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->requestHandler->handle($request);
        } catch (Throwable $e) {
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

        try {
            return $this->view->response($responseDto);
        } catch (Throwable $e) {
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
            return $this->handleRoutedRequest(
                new RoutedRequest(
                    $request,
                    new InternalServerError(RunnerStepEnum::Router, $e, $request),
                    false
                ),
                catch: false,
            );
        }

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
        $requestHandler = new readonly class($this->handleServerRequest(...)) implements RequestHandlerInterface {
            /**
             * @param Closure(ServerRequestInterface):ResponseInterface $handler
             */
            public function __construct(private Closure $handler) {
            }

            #[Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handler)($request);
            }
        };

        foreach ($middlewares as $middleware) {
            $requestHandler = new readonly class($middleware, $requestHandler) implements RequestHandlerInterface {
                public function __construct(
                    private MiddlewareInterface $middleware,
                    private RequestHandlerInterface $next,
                ) {
                }

                #[Override]
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $requestHandler;
    }
}
