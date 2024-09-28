<?php

namespace Arakne\Tests\Spinneret\Security;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Security\AuthenticationCookieHelper;
use Arakne\Spinneret\Security\LoadSessionMiddleware;
use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Spinneret\Security\SecurityModule;
use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUserHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class SecurityModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new SecurityModule();
        $this->assertSame([], $routerModule->presenters());
        $this->assertSame([], $routerModule->renderers());

        $routes = new RouteCollectionBuilder();
        $routerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $app = new Application(true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(SecurityConfig::class, new SecurityConfig());

        $routerModule = new SecurityModule();
        $routerModule->register($container);

        $this->assertInstanceOf(ObjectUserHandler::class, $container->get(UserHandlerInterface::class));
        $this->assertInstanceOf(HmacCookieSerializer::class, $container->get(CookieSerializerInterface::class));
        $this->assertInstanceOf(AuthenticationCookieHelper::class, $container->get(AuthenticationCookieHelper::class));
        $this->assertInstanceOf(LoadSessionMiddleware::class, $container->get(LoadSessionMiddleware::class));
    }

    #[Test]
    public function registerNotEnabledShouldDoNothing()
    {
        $app = new Application(true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(SecurityConfig::class, new SecurityConfig(enabled: false));

        $routerModule = (new SecurityModule())->withConfiguration(new SecurityConfig(enabled: false));
        $routerModule->register($container);

        $this->assertFalse($container->has(UserHandlerInterface::class));
        $this->assertFalse($container->has(CookieSerializerInterface::class));
        $this->assertFalse($container->has(AuthenticationCookieHelper::class));
        $this->assertFalse($container->has(LoadSessionMiddleware::class));
    }

    #[Test]
    public function registerWithCustomUserHandler()
    {
        $app = new Application(true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(TestUserHandler::class, new TestUserHandler());
        $container->set(SecurityConfig::class, $config = new SecurityConfig(userHandler: TestUserHandler::class));

        $routerModule = (new SecurityModule())->withConfiguration($config);
        $routerModule->register($container);

        $this->assertInstanceOf(TestUserHandler::class, $container->get(UserHandlerInterface::class));
        $this->assertInstanceOf(HmacCookieSerializer::class, $container->get(CookieSerializerInterface::class));
        $this->assertInstanceOf(AuthenticationCookieHelper::class, $container->get(AuthenticationCookieHelper::class));
        $this->assertInstanceOf(LoadSessionMiddleware::class, $container->get(LoadSessionMiddleware::class));
    }

    #[Test]
    public function registerWithCustomSerializer()
    {
        $app = new Application(true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(MyCustomSerializer::class, new MyCustomSerializer());
        $container->set(SecurityConfig::class, $config = new SecurityConfig(serializer: MyCustomSerializer::class));

        $routerModule = (new SecurityModule())->withConfiguration($config);
        $routerModule->register($container);

        $this->assertInstanceOf(ObjectUserHandler::class, $container->get(UserHandlerInterface::class));
        $this->assertInstanceOf(MyCustomSerializer::class, $container->get(CookieSerializerInterface::class));
        $this->assertInstanceOf(AuthenticationCookieHelper::class, $container->get(AuthenticationCookieHelper::class));
        $this->assertInstanceOf(LoadSessionMiddleware::class, $container->get(LoadSessionMiddleware::class));
    }
}

class MyCustomSerializer implements CookieSerializerInterface
{
    #[\Override] public function fromString(string $cookie): ?ParsedCookie
    {
        // TODO: Implement fromString() method.
    }

    #[\Override] public function toString(ParsedCookie $cookie): string
    {
        // TODO: Implement toString() method.
    }
}
