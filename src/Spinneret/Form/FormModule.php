<?php

namespace Arakne\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for provide "vincent4vx/form" services
 *
 * @todo i18n
 *
 * Provided services:
 * - {@see FormFactoryInterface} - The factory for create forms
 * - {@see RegistryInterface} - Alias to {@see ContainerRegistry}
 * - {@see CsrfHelper} - Helper for initialize CSRF tokens
 */
final class FormModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(FormFactoryLoader::class, FormFactoryLoader::class)
            ->setArguments([
                new Reference(RegistryInterface::class),
            ])
        ;

        $containerBuilder->register(FormFactoryInterface::class)
            ->setFactory([new Reference(FormFactoryLoader::class), 'load'])
            ->setArguments([
                new Reference(Application::class),
            ])
        ;

        $containerBuilder->register(ContainerRegistry::class, ContainerRegistry::class)
            ->setArguments([
                new Reference('service_container'),
            ])
        ;

        $containerBuilder->register(CsrfHelper::class, CsrfHelper::class)
            ->setArguments([new Reference(FormFactoryInterface::class)])
        ;

        $containerBuilder->setAlias(RegistryInterface::class, ContainerRegistry::class);
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
        return  [];
    }
}
