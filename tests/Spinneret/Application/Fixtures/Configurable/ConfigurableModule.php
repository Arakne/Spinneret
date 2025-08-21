<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Override;

/**
 * @implements ConfigurableModuleInterface<TestConfig>
 */
final readonly class ConfigurableModule implements ConfigurableModuleInterface, RouteConfiguratorInterface
{
    public function __construct(
        private TestConfig $config = new TestConfig(),
    ) {}

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->config;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ShowConfigPresenter::class)->tag(new Presenter(ShowConfigRequest::class));
        $containerBuilder->register(ShowConfigRenderer::class)->tag(new Renderer(ShowConfigResponse::class));
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->get('/config', ShowConfigRequest::class);
    }
}
