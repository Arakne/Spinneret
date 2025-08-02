<?php

namespace Arakne\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Container\ContainerInterface;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\RegistryInterface;

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
        $containerBuilder->register(FormFactoryLoader::class, [
            new Reference(RegistryInterface::class),
        ]);

        $containerBuilder->register(FormFactoryInterface::class)
            ->factory(new Reference(FormFactoryLoader::class)->method('load'))
            ->arg(new Reference(Application::class))
        ;

        $containerBuilder->register(ContainerRegistry::class, [
            new Reference(ContainerInterface::class),
        ]);

        $containerBuilder->register(CsrfHelper::class, [new Reference(FormFactoryInterface::class)]);

        $containerBuilder->alias(RegistryInterface::class, ContainerRegistry::class);
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }
}
