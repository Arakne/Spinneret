<?php

namespace Arakne\Tests\Spinneret\Bus;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Bus\Attribute\MessageHandler;
use Arakne\Spinneret\Bus\BusDispatcher;
use Arakne\Spinneret\Bus\BusDispatcherInterface;
use Arakne\Spinneret\Bus\BusModule;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
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

        $routerModule = new BusModule();
        $routerModule->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);

        $this->assertInstanceOf(BusDispatcher::class, $container->get(BusDispatcherInterface::class));
    }

    #[Test]
    public function registerWithHandlers()
    {
        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->register(FooCommandHandler::class)
            ->tag(MessageHandler::class)
        ;
        $container->register(GenericHandler::class)
            ->tag(new MessageHandler(ArrayObject::class))
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container = $container->build();
        $container->set(Application::class, $app);

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

        $container->register(FooCommand::class)
            ->tag(MessageHandler::class)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);

        $container->build();
    }

    #[Test]
    public function invalidHandlerMissingParameter()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerWithoutParameters must have exactly one parameter');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->register(InvalidHandlerWithoutParameters::class)
            ->tag(MessageHandler::class)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);
        $container->build();
    }

    #[Test]
    public function invalidHandlerTooManyParameters()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerTooManyParameters must have exactly one parameter');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->register(InvalidHandlerTooManyParameters::class)
            ->tag(MessageHandler::class)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);
        $container->build();
    }

    #[Test]
    public function invalidHandlerMissingType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Handler Arakne\Tests\Spinneret\Bus\Fixtures\InvalidHandlerMissingType must have a typed parameter, or use the message attribute to explicitly define the message class');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->register(InvalidHandlerMissingType::class)
            ->tag(MessageHandler::class)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);
        $container->build();
    }

    #[Test]
    public function invalidHandlerTypeNotClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The type object is not a valid message class');

        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->register(InvalidHandlerTypeNotClass::class)
            ->tag(MessageHandler::class)
        ;

        $routerModule = new BusModule();
        $routerModule->register($container);
        $container->build();
    }
}
