<?php

namespace Arakne\Tests\Spinneret\Bus;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Bus\BusDispatcher;
use Arakne\Spinneret\Bus\BusDispatcherInterface;
use Arakne\Spinneret\Bus\BusModule;
use Arakne\Spinneret\Bus\Compiler\RegisterHandlersCompilerPass;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompilerInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteCollectionLoader;
use Arakne\Spinneret\Router\RouteCollectionLoaderInterface;
use Arakne\Spinneret\Router\Router;
use Arakne\Spinneret\Router\RouterConfig;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Router\RouterModule;
use Arakne\Spinneret\Router\UrlMatcherLoader;
use Arakne\Spinneret\Router\UrlMatcherLoaderInterface;
use Arakne\Tests\Spinneret\Bus\Fixtures\FooCommand;
use Arakne\Tests\Spinneret\Bus\Fixtures\FooCommandHandler;
use Arakne\Tests\Spinneret\Bus\Fixtures\GenericHandler;
use Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerMissingType;
use Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerTooManyParameters;
use Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerTypeNotClass;
use Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerWithoutParameters;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class BusModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new BusModule();
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

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();

        $this->assertInstanceOf(BusDispatcher::class, $container->get(BusDispatcherInterface::class));
    }

    #[Test]
    public function registerWithHandlers()
    {
        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(FooCommandHandler::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;
        $container->register(GenericHandler::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG, ['message' => ArrayObject::class])
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();

        $this->assertInstanceOf(BusDispatcher::class, $container->get(BusDispatcherInterface::class));
        $this->assertSame(84, $container->get(BusDispatcherInterface::class)->process(new FooCommand(42), fn ($value) => $value));
        $this->assertSame('9128b91bf1c2146ae8de32e419b304ca', $container->get(BusDispatcherInterface::class)->process(new ArrayObject(['foo', 'bar']), fn ($value) => $value));
    }

    #[Test]
    public function invalidHandler()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\FooCommand must have an __invoke method');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(FooCommand::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();
    }

    #[Test]
    public function invalidHandlerMissingParameter()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerWithoutParameters must have exactly one parameter');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(InvalidHandlerWithoutParameters::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();
    }

    #[Test]
    public function invalidHandlerTooManyParameters()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerTooManyParameters must have exactly one parameter');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(InvalidHandlerTooManyParameters::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();
    }

    #[Test]
    public function invalidHandlerMissingType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerMissingType must have a typed parameter, or use the message attribute to explicitly define the message class');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(InvalidHandlerMissingType::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();
    }

    #[Test]
    public function invalidHandlerTypeNotClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The type object is not a valid message class');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->register(InvalidHandlerTypeNotClass::class)
            ->setAutowired(true)
            ->addTag(RegisterHandlersCompilerPass::TAG)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->getAlias(BusDispatcherInterface::class)->setPublic(true);
        $container->compile();
    }
}
