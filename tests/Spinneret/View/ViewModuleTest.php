<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\View\DispatcherForwarder;
use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\ForwarderInterface;
use Arakne\Spinneret\View\ViewEngineInterface;
use Arakne\Spinneret\View\ViewModule;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Routing\RouteCollection;

class ViewModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new ViewModule();
        $routes = new RouteCollectionBuilder();
        $routerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $container = new ContainerBuilder();

        $routerModule = new ViewModule();
        $routerModule->register($container);
        $container = $container->build();

        $container->set(ResponseFactoryInterface::class, new Psr17Factory());
        $container->set(StreamFactoryInterface::class, new Psr17Factory());
        $container->set(PresenterDispatcherInterface::class, new class implements PresenterDispatcherInterface
        {
            #[\Override] public function dispatch(RoutedRequest $routedRequest): object
            {
                return new \stdClass();
            }
        });

        $this->assertInstanceOf(Engine::class, $container->get(ViewEngineInterface::class));
        $this->assertInstanceOf(DispatcherForwarder::class, $container->get(ForwarderInterface::class));
    }
}
