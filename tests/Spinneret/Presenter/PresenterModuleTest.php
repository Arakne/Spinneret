<?php

namespace Arakne\Tests\Spinneret\Presenter;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Presenter\PresenterModule;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouteCollection;

class PresenterModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new PresenterModule();
        $routes = new RouteCollectionBuilder();
        $routerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $app = new Application(env: 'test');
        $container = new ContainerBuilder();


        $routerModule = new PresenterModule();
        $routerModule->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(FormFactoryInterface::class, DefaultFormFactory::runtime());

        $this->assertInstanceOf(PresenterDispatcher::class, $container->get(PresenterDispatcherInterface::class));
        $this->assertInstanceOf(RequestPresenter::class, $container->get(RequestPresenter::class));
    }
}
