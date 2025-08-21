<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Error;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Result\NotFound;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Override;

final class ErrorModule implements ModuleInterface, RouteConfiguratorInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->findOrRegister(RequestPresenter::class)
            ->tag(new Presenter(NotFound::class))
            ->tag(new Presenter(MethodNotAllowed::class));
        ;

        $containerBuilder->register(RaiseErrorPresenter::class)->tag(new Presenter(RaiseErrorRequest::class));

        $containerBuilder->register(NotFoundRenderer::class)->tag(new Renderer(NotFound::class));
        $containerBuilder->register(MethodNotAllowedRenderer::class)->tag(new Renderer(MethodNotAllowed::class));
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        $builder->get('/error', RaiseErrorRequest::class);
    }
}
