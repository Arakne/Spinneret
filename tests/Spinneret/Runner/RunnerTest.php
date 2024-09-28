<?php

namespace Arakne\Tests\Spinneret\Runner;

use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Router\Router;
use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\Runner\Runner;
use Arakne\Spinneret\View\Engine;
use Arakne\Tests\Spinneret\Runner\Fixtures\Base64ResponseMiddleware;
use Arakne\Tests\Spinneret\Runner\Fixtures\ErrorMiddleware;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooErrorRenderer;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooErrorResponse;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooPresenter;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooRequest;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooSuccessRenderer;
use Arakne\Tests\Spinneret\Runner\Fixtures\FooSuccessResponse;
use Arakne\Tests\Spinneret\Runner\Fixtures\InternalServerErrorPresenter;
use Arakne\Tests\Spinneret\Runner\Fixtures\InternalServerErrorRenderer;
use Arakne\Tests\Spinneret\Runner\Fixtures\ReverseMiddleware;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class RunnerTest extends TestCase
{
    private PresenterDispatcher $presenterDispatcher;
    private Router $router;
    private Engine $view;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $container = new ContainerBuilder();
        $container->autowire(FooPresenter::class, FooPresenter::class);
        $container->autowire(FooSuccessRenderer::class, FooSuccessRenderer::class);
        $container->autowire(FooErrorRenderer::class, FooErrorRenderer::class);
        $container->autowire(InternalServerErrorPresenter::class, InternalServerErrorPresenter::class);
        $container->autowire(InternalServerErrorRenderer::class, InternalServerErrorRenderer::class);

        $this->container = $container;

        $routesBuilder = new RouteCollectionBuilder();
        $routesBuilder->get('/foo', FooRequest::class);
        $routesBuilder->get('/invalid', 'invalid', 'invalid');

        $this->router = new Router(
            new UrlMatcher($routesBuilder->routes, new RequestContext()),
            DefaultFormFactory::runtime()
        );
        $this->presenterDispatcher = new PresenterDispatcher($container, [
            FooRequest::class => FooPresenter::class,
            InternalServerError::class => InternalServerErrorPresenter::class,
        ]);
        $this->view = new Engine(
            $container,
            new Psr17Factory(),
            new Psr17Factory(),
            [
                FooSuccessResponse::class => FooSuccessRenderer::class,
                FooErrorResponse::class => FooErrorRenderer::class,
                InternalServerError::class => InternalServerErrorRenderer::class,
            ]
        );
    }

    #[Test]
    public function handleSuccessSimple()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            logger: $logger = new ArrayLogger()
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $response = $runner->handle($psrRequest);

        $expectedRequest = new FooRequest();
        $expectedRequest->bar = '42';

        $this->assertEquals('{"foo":{"message":"success 42"}}', (string) $response->getBody());
        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Request {{ method }} {{ uri }} was routed to {{ target }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'target' => FooRequest::class,
                    'routedRequest' => $expectedRequest,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => FooSuccessResponse::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 200,
                    'reason' => 'OK',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handleRequestError()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            logger: $logger = new ArrayLogger(),
        );

        $psrRequest = new ServerRequest('GET', '/foo');
        $response = $runner->handle($psrRequest);

        $this->assertEquals('{"error":{"message":"error This value is required"}}', (string) $response->getBody());
        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Request {{ method }} {{ uri }} was routed to {{ target }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'target' => FooRequest::class,
                    'routedRequest' => new FooRequest(),
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => FooErrorResponse::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 400,
                    'reason' => 'Bad Request',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handlePresenterException()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            logger: $logger = new ArrayLogger(),
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=error');
        $response = $runner->handle($psrRequest);

        $parsedRequest = new FooRequest();
        $parsedRequest->bar = 'error';

        $this->assertEquals('{"error":"Exception : runtime error","step":"Presenter","request":{"bar":"error"}}', (string) $response->getBody());
        $this->assertFalse(InternalServerErrorPresenter::$lastRequest->success);
        $this->assertNull(InternalServerErrorPresenter::$lastRequest->form);
        $this->assertEquals($psrRequest->withAttribute('request', $parsedRequest), InternalServerErrorPresenter::$lastRequest->psrRequest);

        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Request {{ method }} {{ uri }} was routed to {{ target }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'target' => FooRequest::class,
                    'routedRequest' => $parsedRequest,
                ],
            ],
            [
                'level' => 'error',
                'message' => 'Error occurs on presenter step for request {{ method }} {{ uri }} : {{ exception }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'exception' => $logger->logs[2]['context']['exception'],
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => InternalServerError::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 500,
                    'reason' => 'Internal Server Error',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handleRouterException()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            logger: $logger = new ArrayLogger(),
        );

        $psrRequest = new ServerRequest('GET', '/invalid');
        $response = $runner->handle($psrRequest);

        $this->assertEquals('{"error":"ReflectionException : Class \"invalid\" does not exist","step":"Router","request":null}', (string) $response->getBody());
        $this->assertFalse(InternalServerErrorPresenter::$lastRequest->success);
        $this->assertNull(InternalServerErrorPresenter::$lastRequest->form);
        $this->assertSame($psrRequest, InternalServerErrorPresenter::$lastRequest->psrRequest);

        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'error',
                'message' => 'Error occurs on router step for request {{ method }} {{ uri }} : {{ exception }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'exception' => $logger->logs[1]['context']['exception'],
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => InternalServerError::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 500,
                    'reason' => 'Internal Server Error',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handleViewException()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            logger: $logger = new ArrayLogger(),
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=view-error');
        $response = $runner->handle($psrRequest);

        $parsedRequest = new FooRequest();
        $parsedRequest->bar = 'view-error';

        $this->assertEquals('{"error":"Exception : view error","step":"View","request":{"bar":"view-error"}}', (string) $response->getBody());
        $this->assertFalse(InternalServerErrorPresenter::$lastRequest->success);
        $this->assertNull(InternalServerErrorPresenter::$lastRequest->form);
        $this->assertEquals($psrRequest->withAttribute('request', $parsedRequest), InternalServerErrorPresenter::$lastRequest->psrRequest);

        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Request {{ method }} {{ uri }} was routed to {{ target }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'target' => FooRequest::class,
                    'routedRequest' => $parsedRequest,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => FooSuccessResponse::class,
                ],
            ],
            [
                'level' => 'error',
                'message' => 'Error occurs on view step for request {{ method }} {{ uri }} : {{ exception }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'exception' => $logger->logs[3]['context']['exception'],
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => InternalServerError::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 500,
                    'reason' => 'Internal Server Error',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handleSuccessWithMiddleware()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            [
                new ReverseMiddleware(),
                new Base64ResponseMiddleware(),
            ],
            logger: $logger = new ArrayLogger(),
        );

        $psrRequest = new ServerRequest('GET', '/foo?rab=hello');
        $response = $runner->handle($psrRequest);

        $expectedRequest = new FooRequest();
        $expectedRequest->bar = 'olleh';

        $this->assertEquals('fX0iaGVsbG8gc3NlY2N1cyI6ImVnYXNzZW0iezoib29mIns=', (string) $response->getBody());
        $this->assertEquals('}}"hello sseccus":"egassem"{:"oof"{', base64_decode((string) $response->getBody()));
        $this->assertEquals([
            [
                'level' => 'info',
                'message' => 'Handling request {{ method }} {{ uri }} from {{ client }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'client' => 'unknown',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Start Middleware {{ middleware }}',
                'context' => [
                    'middleware' => Base64ResponseMiddleware::class,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Start Middleware {{ middleware }}',
                'context' => [
                    'middleware' => ReverseMiddleware::class,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Request {{ method }} {{ uri }} was routed to {{ target }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'target' => FooRequest::class,
                    'routedRequest' => $expectedRequest,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Response DTO {{ dto }} was generated',
                'context' => [
                    'dto' => FooSuccessResponse::class,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'End Middleware {{ middleware }}',
                'context' => [
                    'middleware' => ReverseMiddleware::class,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'End Middleware {{ middleware }}',
                'context' => [
                    'middleware' => Base64ResponseMiddleware::class,
                ],
            ],
            [
                'level' => 'info',
                'message' => 'Response for {{ method }} {{ uri }} : {{ code }} {{ reason }}',
                'context' => [
                    'method' => 'GET',
                    'uri' => $psrRequest->getUri(),
                    'code' => 200,
                    'reason' => 'OK',
                    'headers' => $response->getHeaders(),
                ],
            ],
        ], $logger->logs);
    }

    #[Test]
    public function handleMiddlewareException()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view,
            [
                new ErrorMiddleware(),
            ]
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=error');
        $response = $runner->handle($psrRequest);

        $this->assertEquals('{"error":"Exception : Error","step":"Middleware","request":null}', (string) $response->getBody());
        $this->assertFalse(InternalServerErrorPresenter::$lastRequest->success);
        $this->assertNull(InternalServerErrorPresenter::$lastRequest->form);
        $this->assertSame($psrRequest, InternalServerErrorPresenter::$lastRequest->psrRequest);
    }

    #[Test]
    public function handleRoutedRequestSuccessSimple()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $req = new FooRequest();
        $req->bar = 'test';
        $routedRequest = new RoutedRequest(
            $psrRequest,
            $req,
        );

        $response = $runner->handleRoutedRequest($routedRequest, false);

        $this->assertEquals('{"foo":{"message":"success test"}}', (string) $response->getBody());
    }

    #[Test]
    public function handleRoutedRequestPresenterExceptionNotCatch()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('runtime error');

        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $req = new FooRequest();
        $req->bar = 'error';
        $routedRequest = new RoutedRequest(
            $psrRequest,
            $req,
        );

        $runner->handleRoutedRequest($routedRequest, false);
    }

    #[Test]
    public function handleRoutedRequestPresenterExceptionCatch()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $req = new FooRequest();
        $req->bar = 'error';
        $routedRequest = new RoutedRequest(
            $psrRequest,
            $req,
        );

        $response = $runner->handleRoutedRequest($routedRequest, true);

        $this->assertEquals('{"error":"Exception : runtime error","step":"Presenter","request":{"bar":"error"}}', (string) $response->getBody());
    }

    #[Test]
    public function handleRoutedRequestViewExceptionNotCatch()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('view error');

        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $req = new FooRequest();
        $req->bar = 'view-error';
        $routedRequest = new RoutedRequest(
            $psrRequest,
            $req,
        );

        $runner->handleRoutedRequest($routedRequest, false);
    }

    #[Test]
    public function handleRoutedRequestViewExceptionCatch()
    {
        $runner = new Runner(
            $this->router,
            $this->presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=42');
        $req = new FooRequest();
        $req->bar = 'view-error';
        $routedRequest = new RoutedRequest(
            $psrRequest,
            $req,
        );

        $response = $runner->handleRoutedRequest($routedRequest, true);

        $this->assertEquals('{"error":"Exception : view error","step":"View","request":{"bar":"view-error"}}', (string) $response->getBody());
    }

    #[Test]
    public function handleErrorDuringErrorHandlingShouldNotResultToInfiniteLoopAfterPresenterError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No presenter found for Arakne\Spinneret\Runner\InternalServerError');

        $presenterDispatcher = new PresenterDispatcher($this->container, [
            FooRequest::class => FooPresenter::class,
        ]);
        $runner = new Runner(
            $this->router,
            $presenterDispatcher,
            $this->view
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=error');
        $runner->handle($psrRequest);
    }

    #[Test]
    public function handleErrorDuringErrorHandlingShouldNotResultToInfiniteLoopAfterMiddlewareError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No presenter found for Arakne\Spinneret\Runner\InternalServerError');

        $presenterDispatcher = new PresenterDispatcher($this->container, [
            FooRequest::class => FooPresenter::class,
        ]);

        $runner = new Runner(
            $this->router,
            $presenterDispatcher,
            $this->view,
            [
                new ErrorMiddleware(),
            ]
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=error');
        $runner->handle($psrRequest);
    }

    #[Test]
    public function handleErrorDuringErrorHandlingShouldNotResultToInfiniteLoopAfterViewError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No presenter found for Arakne\Spinneret\Runner\InternalServerError');

        $presenterDispatcher = new PresenterDispatcher($this->container, [
            FooRequest::class => FooPresenter::class,
        ]);

        $runner = new Runner(
            $this->router,
            $presenterDispatcher,
            $this->view,
            [
                new ErrorMiddleware(),
            ]
        );

        $psrRequest = new ServerRequest('GET', '/foo?bar=view-error');
        $runner->handle($psrRequest);
    }

    #[Test]
    public function handleErrorDuringErrorHandlingShouldNotResultToInfiniteLoopAfterRouterError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No presenter found for Arakne\Spinneret\Runner\InternalServerError');

        $presenterDispatcher = new PresenterDispatcher($this->container, [
            FooRequest::class => FooPresenter::class,
        ]);

        $runner = new Runner(
            $this->router,
            $presenterDispatcher,
            $this->view,
            [
                new ErrorMiddleware(),
            ]
        );

        $psrRequest = new ServerRequest('GET', '/invalid');
        $runner->handle($psrRequest);
    }
}
