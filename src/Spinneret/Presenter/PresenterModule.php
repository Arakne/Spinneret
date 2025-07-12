<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Presenter\Compiler\RegisterPresentersCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
        $containerBuilder->addCompilerPass(new RegisterPresentersCompilerPass());

        $containerBuilder->register(PresenterDispatcher::class, PresenterDispatcher::class)
            ->setArguments([
                new Reference('service_container'),
                new AbstractArgument('Defined by ' . RegisterPresentersCompilerPass::class),
            ])
        ;

        $containerBuilder->setAlias(PresenterDispatcherInterface::class, PresenterDispatcher::class);

        $containerBuilder->register(RequestPresenter::class, RequestPresenter::class)
            ->setPublic(true)
        ;
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void {}
}
