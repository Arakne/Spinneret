<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\DatabaseModule;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Tests\Spinneret\Database\Fixtures\MyEntityModule;
use Arakne\Tests\Spinneret\Database\Fixtures\MyEntityRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class DatabaseModuleTest extends TestCase
{

    #[Test]
    public function emptyMethods()
    {
        $routerModule = new DatabaseModule();
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
        $container->set(DatabaseConfig::class, new DatabaseConfig(
            new ConnectionConfig('test', 'sqlite::memory:')
        ));

        $routerModule = new DatabaseModule();
        $routerModule->register($container);

        $this->assertInstanceOf(DatabaseConnectionManager::class, $container->get(DatabaseConnectionManagerInterface::class));
        $this->assertInstanceOf(DatabaseConnection::class, $container->get(DatabaseConnectionManager::class)->get('test'));
    }

    #[Test]
    public function registerWithLogger()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, new DatabaseConfig(
            new ConnectionConfig('test', 'sqlite::memory:')
        ));
        $container->set(LoggerInterface::class, $logger = new ArrayLogger());

        $routerModule = new DatabaseModule();
        $routerModule->register($container);

        $this->assertInstanceOf(DatabaseConnectionManager::class, $container->get(DatabaseConnectionManagerInterface::class));
        $this->assertInstanceOf(DatabaseConnection::class, $container->get(DatabaseConnectionManager::class)->get('test'));

        $container->get(DatabaseConnectionManager::class)->get('test')->query('SELECT 1');

        $this->assertEquals([
            [
                'level' => 'debug',
                'message' => '[test] Execute read query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT 1',
                    'database' => 'test',
                ]
            ],
            [
                'level' => 'debug',
                'message' => '[test] Connect to database {{ dsn }}',
                'context' => [
                    'dsn' => 'sqlite::memory:',
                    'database' => 'test',
                ]
            ],
        ], $logger->logs);
    }

    #[Test]
    public function functionalShouldInjectDatabaseConnection()
    {
        $app = new class(isDev: true, env: 'test') extends Application {
           public function configDir(): string
           {
               return __DIR__ . '/Fixtures/config';
           }

           protected function applicationModules(): array
           {
               return [
                   new DatabaseModule(),
                   new MyEntityModule(),
               ];
           }
        };

        $repository = $app->get(MyEntityRepository::class);
        $repository->init();

        $this->assertSame([], $repository->all());
    }
}
