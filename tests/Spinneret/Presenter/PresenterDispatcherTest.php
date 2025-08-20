<?php

namespace Arakne\Tests\Spinneret\Presenter;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Tests\Spinneret\Presenter\Fixtures\MyPresenter;
use Arakne\Tests\Spinneret\Presenter\Fixtures\MyRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PresenterDispatcherTest extends TestCase
{
    #[Test]
    public function dispatchSuccess()
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set(MyPresenter::class, $presenter = new MyPresenter());
        $container = $container->build();
        $dispatcher = new PresenterDispatcher($container, [
            MyRequest::class => MyPresenter::class,
        ]);

        $routedRequest = new RoutedRequest(
            new ServerRequest('GET', '/'),
            new MyRequest()
        );

        $this->assertEquals((object) ['ok' => true], $dispatcher->dispatch($routedRequest));
        $this->assertSame($routedRequest->routedRequest, $presenter->request);
        $this->assertSame($routedRequest, $presenter->routedRequest);
        $this->assertSame('handleSuccess', $presenter->method);
    }

    #[Test]
    public function dispatchError()
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set(MyPresenter::class, $presenter = new MyPresenter());
        $container = $container->build();
        $dispatcher = new PresenterDispatcher($container, [
            MyRequest::class => MyPresenter::class,
        ]);

        $routedRequest = new RoutedRequest(
            new ServerRequest('GET', '/'),
            new MyRequest(),
            false
        );

        $this->assertEquals((object) ['ok' => false], $dispatcher->dispatch($routedRequest));
        $this->assertSame($routedRequest->routedRequest, $presenter->request);
        $this->assertSame($routedRequest, $presenter->routedRequest);
        $this->assertSame('handleError', $presenter->method);
    }

    #[Test]
    public function dispatchPresenterNotFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No presenter found for Arakne\Tests\Spinneret\Presenter\Fixtures\MyRequest');

        $container = new ContainerBuilder(registerAsPublic: true);
        $container = $container->build();
        $dispatcher = new PresenterDispatcher($container, []);

        $routedRequest = new RoutedRequest(
            new ServerRequest('GET', '/'),
            new MyRequest(),
            false
        );

        $dispatcher->dispatch($routedRequest);
    }
}
