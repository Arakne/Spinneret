<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Database\Compiler\SetConnectionCompilerPass;
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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
    ) {
    }

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
        $containerBuilder->addCompilerPass(new SetConnectionCompilerPass());

        $containerBuilder->register(DatabaseConnectionManager::class, DatabaseConnectionManager::class)
            ->setArguments([
                new Reference(DatabaseConfig::class),
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        $containerBuilder->setAlias(DatabaseConnectionManagerInterface::class, DatabaseConnectionManager::class);

        if ($this->config->useMigration) {
            $this->registerMigration($containerBuilder);
        }
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    #[Override]
    public function presenters(): array
    {
        return [];
    }

    #[Override]
    public function renderers(): array
    {
        return [];
    }

    private function registerMigration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(MigrationManager::class, MigrationManager::class)
            ->setArguments([
                new Reference(MigrationRepositoryInterface::class),
                new Reference(DatabaseConnectionManagerInterface::class),
                new ServiceClosureArgument(new TaggedIteratorArgument(tag: MigrationInterface::class)),
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        if ($this->config->migrationConnection !== null) {
            $connection = $this->config->migrationConnection;

            if ($connection instanceof UnitEnum) {
                $connection = $connection->name;
            }

            $containerBuilder
                ->register(SqlMigrationRepository::class, SqlMigrationRepository::class)
                ->setArguments([
                    (new Definition(DatabaseConnectionInterface::class))
                        ->setFactory([new Reference(DatabaseConnectionManagerInterface::class), 'get'])
                        ->setArguments([$connection]),
                    new Reference(ClockInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                ])
            ;

            $containerBuilder->setAlias(MigrationRepositoryInterface::class, SqlMigrationRepository::class);
        } else {
            $containerBuilder->register(NullMigrationRepository::class, NullMigrationRepository::class);
            $containerBuilder->setAlias(MigrationRepositoryInterface::class, NullMigrationRepository::class);
        }

        $containerBuilder->registerForAutoconfiguration(MigrationInterface::class)
            ->addTag(MigrationInterface::class)
        ;

        $containerBuilder->register(MigrationStatusCommand::class, MigrationStatusCommand::class)
            ->setArguments([new Reference(MigrationManager::class)])
            ->addTag(Command::class)
            ->setPublic(true)
        ;

        $containerBuilder->register(MigrationUpCommand::class, MigrationUpCommand::class)
            ->setArguments([new Reference(MigrationManager::class)])
            ->addTag(Command::class)
            ->setPublic(true)
        ;

        $containerBuilder->register(MigrationDownCommand::class, MigrationDownCommand::class)
            ->setArguments([new Reference(MigrationManager::class)])
            ->addTag(Command::class)
            ->setPublic(true)
        ;
    }
}
