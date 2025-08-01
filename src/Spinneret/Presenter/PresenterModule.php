<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Processor\RegisterPresentersProcessor;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Container\ContainerInterface;

/**
 * Register services for the presenter module
 *
 * Provided services:
 * - {@see PresenterDispatcherInterface} - Alias to {@see PresenterDispatcher}
 * - {@see RequestPresenter}
 *
 * Used tags:
 * - PresenterInterface::class: Used to register presenters in the container.
 *                              The "request" parameter must be defined with the associated request class name.
 */
final class PresenterModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->processor(new RegisterPresentersProcessor());

        $containerBuilder->register(PresenterDispatcher::class, [
            new Reference(ContainerInterface::class),
            [],
        ]);

        $containerBuilder->alias(PresenterDispatcherInterface::class, PresenterDispatcher::class);
        $containerBuilder->register(RequestPresenter::class)->public();
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void {}
}
