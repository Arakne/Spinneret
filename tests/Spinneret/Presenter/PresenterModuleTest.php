<?php

namespace Arakne\Tests\Spinneret\Presenter;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Presenter\PresenterModule;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

        $container->set(Application::class, $app);
        $container->set(FormFactoryInterface::class, DefaultFormFactory::runtime());

        $routerModule = new PresenterModule();
        $routerModule->register($container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }

        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }

        $container->compile();

        $this->assertInstanceOf(PresenterDispatcher::class, $container->get(PresenterDispatcherInterface::class));
        $this->assertInstanceOf(RequestPresenter::class, $container->get(RequestPresenter::class));
    }
}
