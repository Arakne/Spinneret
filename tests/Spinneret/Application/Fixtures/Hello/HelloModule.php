<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Hello;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Override;

final class HelloModule implements ModuleInterface, RouteConfiguratorInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HelloPresenter::class)->tag(new Presenter(HelloRequest::class));
        $containerBuilder->register(HelloRenderer::class)->tag(new Renderer(HelloResponse::class));
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->get('/hello', HelloRequest::class);
    }
}
