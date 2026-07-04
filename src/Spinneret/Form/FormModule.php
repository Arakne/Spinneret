<?php

namespace Arakne\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Override;
use Psr\Container\ContainerInterface;
use Quatrevieux\Form\Choice\ChoicesProviderInterface;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\RegistryInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintValidatorInterface;

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

        $containerBuilder->configureInstanceOf(ConstraintValidatorInterface::class, static function (ServiceBuilder $service) {
            $service->public();
        });

        $containerBuilder->configureInstanceOf(ChoicesProviderInterface::class, static function (ServiceBuilder $service) {
            $service->public();
        });

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
}
