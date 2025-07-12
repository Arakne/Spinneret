<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\Console;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\DatabaseModule;
use Arakne\Spinneret\Database\Migration\Console\MigrationDownCommand;
use Arakne\Spinneret\Database\Migration\Console\MigrationStatusCommand;
use Arakne\Spinneret\Database\Migration\Console\MigrationUpCommand;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Spinneret\Database\Migration\Repository\MigrationRepositoryInterface;
use Arakne\Spinneret\Database\Migration\Repository\NullMigrationRepository;
use Arakne\Spinneret\Database\Migration\Repository\SqlMigrationRepository;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Tests\Spinneret\Database\Fixtures\MyEntityModule;
use Arakne\Tests\Spinneret\Database\Fixtures\MyEntityRepository;
use Arakne\Tests\Spinneret\Database\Fixtures\OtherRepository;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\AddEntitiesMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\CreateStructureMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\SeparateNameColumnsMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\SkippedMigration;
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
        $databaseModule = new DatabaseModule();
        $routes = new RouteCollectionBuilder();
        $databaseModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, $config = new DatabaseConfig(
            connections: [new ConnectionConfig('test', 'sqlite::memory:')],
            useMigration: false,
        ));

        $databaseModule = new DatabaseModule();
        $databaseModule->withConfiguration($config)->register($container);

        $this->assertInstanceOf(DatabaseConnectionManager::class, $container->get(DatabaseConnectionManagerInterface::class));
        $this->assertInstanceOf(DatabaseConnection::class, $container->get(DatabaseConnectionManager::class)->get('test'));
        $this->assertFalse($container->has(MigrationManager::class));
    }

    #[Test]
    public function registerWithMigrationWithoutMigrationRepository()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, $config = new DatabaseConfig(
            connections: [new ConnectionConfig('test', 'sqlite::memory:')],
            useMigration: true,
        ));

        $databaseModule = new DatabaseModule();
        $databaseModule->withConfiguration($config)->register($container);

        $this->assertInstanceOf(DatabaseConnectionManager::class, $container->get(DatabaseConnectionManagerInterface::class));
        $this->assertInstanceOf(DatabaseConnection::class, $container->get(DatabaseConnectionManager::class)->get('test'));
        $this->assertInstanceOf(MigrationManager::class, $container->get(MigrationManager::class));
        $this->assertInstanceOf(NullMigrationRepository::class, $container->get(MigrationRepositoryInterface::class));
    }

    #[Test]
    public function registerWithMigrationWithMigrationRepository()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, $config = new DatabaseConfig(
            connections: [new ConnectionConfig('test', 'sqlite::memory:')],
            useMigration: true,
            migrationConnection: 'test',
        ));

        $databaseModule = new DatabaseModule();
        $databaseModule->withConfiguration($config)->register($container);

        $this->assertInstanceOf(DatabaseConnectionManager::class, $container->get(DatabaseConnectionManagerInterface::class));
        $this->assertInstanceOf(DatabaseConnection::class, $container->get(DatabaseConnectionManager::class)->get('test'));
        $this->assertInstanceOf(MigrationManager::class, $container->get(MigrationManager::class));
        $this->assertInstanceOf(SqlMigrationRepository::class, $container->get(MigrationRepositoryInterface::class));
    }

    #[Test]
    public function registerWithMigrationShouldResolveMigrationFromInterface()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, $config = new DatabaseConfig(
            connections: [new ConnectionConfig('test', 'sqlite::memory:')],
            useMigration: true,
            migrationConnection: 'test',
        ));

        $databaseModule = new DatabaseModule();
        $databaseModule->withConfiguration($config)->register($container);

        $container->register(AddEntitiesMigration::class)->setAutoconfigured(true);
        $container->register(CreateStructureMigration::class)->setAutoconfigured(true);
        $container->register(SeparateNameColumnsMigration::class)->setAutoconfigured(true);
        $container->register(SkippedMigration::class)->setAutoconfigured(true);

        $container->findDefinition(MigrationManager::class)->setPublic(true);

        $container->compile();

        $manager = $container->get(MigrationManager::class);
        $migrationsResolver = (new \ReflectionProperty(MigrationManager::class, 'migrationsResolver'))->getValue($manager);
        $migrations = iterator_to_array($migrationsResolver());
        usort($migrations, fn($a, $b) => $a->name() <=> $b->name());

        $this->assertCount(4, $migrations);
        $this->assertInstanceOf(AddEntitiesMigration::class, $migrations[0]);
        $this->assertInstanceOf(CreateStructureMigration::class, $migrations[1]);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $migrations[2]);
        $this->assertInstanceOf(SkippedMigration::class, $migrations[3]);
    }

    #[Test]
    public function registerWithLogger()
    {
        $container = new ContainerBuilder();
        $container->set(DatabaseConfig::class, new DatabaseConfig(
            connections: [new ConnectionConfig('test', 'sqlite::memory:')]
        ));
        $container->set(LoggerInterface::class, $logger = new ArrayLogger());

        $databaseModule = new DatabaseModule();
        $databaseModule->register($container);

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

    #[Test]
    public function functionalShouldInjectMultipleDatabaseConnections()
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

        $repository = $app->get(OtherRepository::class);

        $this->assertSame('test', $repository->test->name());
        $this->assertSame('other', $repository->other->name());
    }

    #[Test]
    public function functionalWithMigrationShouldRegisterCommands()
    {
        $app = new class(isDev: true, env: 'test') extends Application {
           public function configDir(): string
           {
               return __DIR__ . '/Fixtures/config';
           }

           protected function applicationModules(): array
           {
               return [
                   new ConsoleModule(),
                   new DatabaseModule(),
                   new MyEntityModule(),
               ];
           }
        };

        $console = $app->get(Console::class);
        $this->assertInstanceOf(MigrationStatusCommand::class, $console->get('db:migration:status'));
        $this->assertInstanceOf(MigrationUpCommand::class, $console->get('db:migration:up'));
        $this->assertInstanceOf(MigrationDownCommand::class, $console->get('db:migration:down'));
    }
}
