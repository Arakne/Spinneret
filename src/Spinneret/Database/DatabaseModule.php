<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Database\Compiler\SetConnectionCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @implements ConfigurableModuleInterface<DatabaseConfig>
 */
final class DatabaseModule implements ConfigurableModuleInterface
{
    public function __construct(
        private readonly DatabaseConfig $config = new DatabaseConfig(),
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
            ->setArguments([new Reference(DatabaseConfig::class)])
        ;
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
