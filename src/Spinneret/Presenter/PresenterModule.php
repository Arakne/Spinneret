<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register services for the presenter module
 *
 * Required parameters:
 * - spinneret.presenters: The array mapping of request class name to presenter class name. Use {@see PresenterModule::PRESENTERS_PARAMETER} instead of hardcoding the parameter name.
 *
 * Provided services:
 * - {@see PresenterDispatcherInterface} - Alias to {@see PresenterDispatcher}
 * - {@see RequestPresenter}
 */
final class PresenterModule implements ModuleInterface
{
    public const string PRESENTERS_PARAMETER = 'spinneret.presenters';

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->setParameter(self::PRESENTERS_PARAMETER, []);
        $containerBuilder->register(PresenterDispatcher::class, PresenterDispatcher::class)
            ->setArguments([
                new Reference('service_container'),
                '%'.self::PRESENTERS_PARAMETER.'%',
            ])
        ;

        $containerBuilder->setAlias(PresenterDispatcherInterface::class, PresenterDispatcher::class);

        $containerBuilder->register(RequestPresenter::class, RequestPresenter::class)
            ->setPublic(true)
        ;
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {

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
