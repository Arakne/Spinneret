<?php

namespace Arakne\Spinneret\Event;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Event\Compiler\RegisterListenersCompilerPass;
use Arakne\Spinneret\Event\Compiler\RegisterSubscribersCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for register the event dispatcher.
 *
 * This module will resolve the listeners from the container, using the tag "spinneret.event?listener" (cf: {@see RegisterListenersCompilerPass::TAG}).
 * To handled message can be explicitly defined using the "event" attribute on the tag, or it will be resolved from the argument of the listener.
 *
 * Optional services:
 * - {@see LoggerInterface} - to enable logging of dispatched events
 *
 * Provided services:
 * - {@see EventDispatcherInterface} - alias to {@see EventDispatcher}
 */
final readonly class EventModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new RegisterListenersCompilerPass());
        $containerBuilder->addCompilerPass(new RegisterSubscribersCompilerPass());
        $containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag(EventSubscriberInterface::class)
        ;

        $containerBuilder->register(EventDispatcher::class, EventDispatcher::class)
            ->setArguments([
                new Reference('service_container'),
                [], // Listeners are resolved by the compiler pass
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        $containerBuilder->setAlias(EventDispatcherInterface::class, EventDispatcher::class);
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
        return [];
    }
}
