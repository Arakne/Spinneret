<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\ViewEngineInterface;
use Arakne\Spinneret\View\ViewModule;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class ViewModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new ViewModule();
        $this->assertSame([], $routerModule->presenters());
        $this->assertSame([], $routerModule->renderers());

        $routes = new RouteCollectionBuilder();
        $routerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $container = new ContainerBuilder();
        $container->setParameter(ViewModule::RENDERERS_PARAMETER, []);
        $container->set(ResponseFactoryInterface::class, new Psr17Factory());
        $container->set(StreamFactoryInterface::class, new Psr17Factory());

        $routerModule = new ViewModule();
        $routerModule->register($container);

        $this->assertInstanceOf(Engine::class, $container->get(ViewEngineInterface::class));
    }
}
