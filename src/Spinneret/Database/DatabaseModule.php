<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\TaggedServiceIterator;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Database\Argument\DatabaseConnection as DatabaseConnectionArgument;
use Arakne\Spinneret\Database\Migration\Console\MigrationDownCommand;
use Arakne\Spinneret\Database\Migration\Console\MigrationStatusCommand;
use Arakne\Spinneret\Database\Migration\Console\MigrationUpCommand;
use Arakne\Spinneret\Database\Migration\MigrationInterface;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Spinneret\Database\Migration\Repository\MigrationRepositoryInterface;
use Arakne\Spinneret\Database\Migration\Repository\NullMigrationRepository;
use Arakne\Spinneret\Database\Migration\Repository\SqlMigrationRepository;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use UnitEnum;

/**
 * Module to manage SQL databases
 *
 * Provided services:
 * - {@see DatabaseConnectionManagerInterface} - Alias to {@see DatabaseConnectionManager}
 * - {@see MigrationManager} - If {@see DatabaseConfig::$useMigration} is set to true
 * - {@see MigrationRepositoryInterface} - If {@see DatabaseConfig::$useMigration} is set to true
 *
 * Provided commands:
 * - {@see MigrationStatusCommand} - with command name `database:migration:status`
 * - {@see MigrationUpCommand} - with command name `database:migration:up`
 * - {@see MigrationDownCommand} - with command name `database:migration:down`
 *
 * To allow injection of the database connection in repositories, the repositories must be tagged with the tag `spinneret.db.repository`
 * with the attribute `connection` set to the connection name.
 *
 * @implements ConfigurableModuleInterface<DatabaseConfig>
 */
final readonly class DatabaseModule implements ConfigurableModuleInterface
{
    public function __construct(
        private DatabaseConfig $config = new DatabaseConfig(),
    ) {}

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): DatabaseConfig
    {
        return $this->config;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(DatabaseConnectionManager::class, [
            new Reference(DatabaseConfig::class),
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        $containerBuilder->alias(DatabaseConnectionManagerInterface::class, DatabaseConnectionManager::class);

        if ($this->config->useMigration) {
            $this->registerMigration($containerBuilder);
        }
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    private function registerMigration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(MigrationManager::class, [
            new Reference(MigrationRepositoryInterface::class),
            new Reference(DatabaseConnectionManagerInterface::class),
            new TaggedServiceIterator(MigrationInterface::class)->asClosure(),
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        if ($this->config->migrationConnection !== null) {
            $connection = $this->config->migrationConnection;

            if ($connection instanceof UnitEnum) {
                $connection = $connection->name;
            }

            $containerBuilder
                ->register(SqlMigrationRepository::class, [
                    new DatabaseConnectionArgument($connection),
                    new Reference(ClockInterface::class, nullOnInvalid: true),
                ])
            ;

            $containerBuilder->alias(MigrationRepositoryInterface::class, SqlMigrationRepository::class);
        } else {
            $containerBuilder->register(NullMigrationRepository::class);
            $containerBuilder->alias(MigrationRepositoryInterface::class, NullMigrationRepository::class);
        }

        $containerBuilder->configureInstanceOf(MigrationInterface::class, static function (ServiceBuilder $service) {
            $service->tag(MigrationInterface::class);
        });

        $containerBuilder->register(MigrationStatusCommand::class, [new Reference(MigrationManager::class)]);
        $containerBuilder->register(MigrationUpCommand::class, [new Reference(MigrationManager::class)]);
        $containerBuilder->register(MigrationDownCommand::class, [new Reference(MigrationManager::class)]);
    }
}

/**
 * Get an inline service for inject a database connection
 *
 * @param string|UnitEnum $name The connection name
 * @return ValueInterface
 */
function database_connection(string|UnitEnum $name): ValueInterface
{
    return new DatabaseConnectionArgument($name);
}
