<?php

namespace Arakne\Tests\Spinneret\Runner;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Runner\Backend\Httpd\HttpdBackend;
use Arakne\Spinneret\Runner\Runner;
use Arakne\Spinneret\Runner\RunnerConfig;
use Arakne\Spinneret\Runner\RunnerInterface;
use Arakne\Spinneret\Runner\RunnerModule;
use Arakne\Spinneret\View\ViewEngineInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class RunnerModuleTest extends TestCase
{

    #[Test]
    public function emptyMethods()
    {
        $runnerModule = new RunnerModule();
        $this->assertSame([], $runnerModule->presenters());
        $this->assertSame([], $runnerModule->renderers());

        $routes = new RouteCollectionBuilder();
        $runnerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(RunnerConfig::class, new RunnerConfig());
        $container->set(RouterInterface::class, $this->createMock(RouterInterface::class));
        $container->set(PresenterDispatcherInterface::class, $this->createMock(PresenterDispatcherInterface::class));
        $container->set(ViewEngineInterface::class, $this->createMock(ViewEngineInterface::class));

        $runnerModule = new RunnerModule();
        $runnerModule->register($container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }

        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }

        $container->compile();

        $this->assertInstanceOf(Runner::class, $container->get(RunnerInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(ResponseFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(ServerRequestFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(StreamFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(UriFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(UploadedFileFactoryInterface::class));

        $this->assertInstanceOf(HttpdBackend::class, $container->get(HttpdBackend::class));
        $this->assertInstanceOf(ServerRequestCreatorInterface::class, $container->get(ServerRequestCreatorInterface::class));
    }

    #[Test]
    public function registerDisableHttpd()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(RunnerConfig::class, $conf = new RunnerConfig(httpd: false));
        $container->set(RouterInterface::class, $this->createMock(RouterInterface::class));
        $container->set(PresenterDispatcherInterface::class, $this->createMock(PresenterDispatcherInterface::class));
        $container->set(ViewEngineInterface::class, $this->createMock(ViewEngineInterface::class));

        $runnerModule = new RunnerModule();
        $runnerModule = $runnerModule->withConfiguration($conf);
        $runnerModule->register($container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }

        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }

        $container->compile();

        $this->assertInstanceOf(Runner::class, $container->get(RunnerInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(ResponseFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(ServerRequestFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(StreamFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(UriFactoryInterface::class));
        $this->assertInstanceOf(Psr17Factory::class, $container->get(UploadedFileFactoryInterface::class));

        $this->assertFalse($container->has(HttpdBackend::class));
    }
}
