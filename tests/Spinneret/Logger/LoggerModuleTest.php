<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\Driver\FileLogger;
use Arakne\Spinneret\Logger\LoggerConfiguration;
use Arakne\Spinneret\Logger\LoggerDispatcher;
use Arakne\Spinneret\Logger\LoggerFilter;
use Arakne\Spinneret\Logger\LoggerModule;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class LoggerModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $module = new LoggerModule();
        $this->assertSame([], $module->presenters());
        $this->assertSame([], $module->renderers());

        $routes = new RouteCollectionBuilder();
        $module->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $app = new Application(env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(LoggerConfiguration::class, new LoggerConfiguration());

        $module = new LoggerModule();
        $module->register($container);

        $this->assertInstanceOf(LoggerDispatcher::class, $container->get(LoggerInterface::class));
    }

    #[Test]
    public function registerWithConfig()
    {
        $app = new class(true, env: 'test') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }
        };
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);
        $container->set(LoggerConfiguration::class, $app->config()[LoggerConfiguration::class]);
        $container->set(ArrayLogger::class, $arrayLogger = new ArrayLogger());
        $container->setParameter('app.log_dir', $app->logDir());

        $module = new LoggerModule();
        $module = $module->withConfiguration($app->config()[LoggerConfiguration::class]);
        $module->register($container);

        $this->assertInstanceOf(LoggerDispatcher::class, $container->get(LoggerInterface::class));
        $this->assertNull($module->configuration()->getFilter('not-exists'));
        $this->assertIsCallable($module->configuration()->getFilter(3));

        /** @var LoggerFilter[] $loggers */
        $loggers = (new \ReflectionProperty(LoggerDispatcher::class, 'loggers'))->getValue($container->get(LoggerDispatcher::class));

        $this->assertCount(4, $loggers);

        $this->assertSame(2, $loggers[0]->levelMin);
        $this->assertNull($loggers[0]->levelMax);
        $this->assertSame([], $loggers[0]->contextKeys);
        $this->assertNull($loggers[0]->filter);
        $this->assertEquals(new FileLogger($app->logDir().'/app.log'), $loggers[0]->logger);

        $this->assertNull($loggers[1]->levelMin);
        $this->assertSame(1, $loggers[1]->levelMax);
        $this->assertSame(['debug'], $loggers[1]->contextKeys);
        $this->assertNull($loggers[1]->filter);
        $this->assertEquals(new FileLogger($app->logDir().'/debug.log', bufferSize: 10), $loggers[1]->logger);

        $this->assertNull($loggers[2]->levelMin);
        $this->assertNull($loggers[2]->levelMax);
        $this->assertSame([], $loggers[2]->contextKeys);
        $this->assertNull($loggers[2]->filter);
        $this->assertEquals($arrayLogger, $loggers[2]->logger);

        $this->assertNull($loggers[3]->levelMin);
        $this->assertNull($loggers[3]->levelMax);
        $this->assertSame([], $loggers[3]->contextKeys);
        $this->assertNotNull($loggers[3]->filter);
        $this->assertEquals(new FileLogger($app->logDir().'/test.log'), $loggers[3]->logger);

        $filter = $loggers[3]->filter;
        $this->assertTrue($filter(1, 'a test b', ['foo' => 'bar', 'baz' => 'qux']));
        $this->assertFalse($filter(1, 'a a b', ['foo' => 'bar', 'baz' => 'qux']));
        $this->assertFalse($filter(1, 'a test b', ['foo' => 'bar']));
    }
}
