<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Override;

final class DownloadModule implements ModuleInterface, RouteConfiguratorInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(DownloadPresenter::class)->tag(new Presenter(DownloadRequest::class));
        $containerBuilder->register(DownloadRenderer::class)->tag(new Renderer(DownloadResponse::class));
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->get('/download', DownloadRequest::class);
    }
}
