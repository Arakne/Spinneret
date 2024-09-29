<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Database\Compiler\SetConnectionCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module to manage SQL databases
 *
 * Provided services:
 * - {@see DatabaseConnectionManagerInterface} - Alias to {@see DatabaseConnectionManager}
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
}
