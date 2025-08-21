<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Forward;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;

class TestForwardModule implements ModuleInterface, RouteConfiguratorInterface
{
    #[\Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(TestForwardPresenter::class)->tag(new Presenter(TestForwardRequest::class));
        $containerBuilder->register(TestForwardRenderer::class)->tag(new Renderer(TestForwardResponse::class));
    }

    #[\Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->get('/forward', TestForwardRequest::class);
    }
}
