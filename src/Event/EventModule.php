<?php

namespace Arakne\Spinneret\Event;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Event\Attribute\EventListener;
use Arakne\Spinneret\Event\Processor\RegisterListenersProcessor;
use Override;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Module for register the event dispatcher.
 *
 * This module will resolve the listeners from the container, using the tag {@see EventListener}.
 *
 * Optional services:
 * - {@see LoggerInterface} - to enable logging of dispatched events
 *
 * Provided services:
 * - {@see EventDispatcherInterface} - alias to {@see EventDispatcher}
 * - {@see ListenerProviderInterface} - alias to {@see ContainerListenerProvider}
 */
final readonly class EventModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->processor(new RegisterListenersProcessor());

        $containerBuilder->register(ContainerListenerProvider::class, [
            new Reference(ContainerInterface::class),
            [],
        ]);

        $containerBuilder->register(EventDispatcher::class, [
            new Reference(ListenerProviderInterface::class),
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        $containerBuilder->alias(ListenerProviderInterface::class, ContainerListenerProvider::class);
        $containerBuilder->alias(EventDispatcherInterface::class, EventDispatcher::class);
    }
}
