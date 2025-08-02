<?php

namespace Arakne\Spinneret\Event;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Event\Processor\RegisterListenersProcessor;
use Arakne\Spinneret\Event\Processor\RegisterSubscribersProcessor;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Module for register the event dispatcher.
 *
 * This module will resolve the listeners from the container, using the tag "spinneret.event?listener" (cf: {@see RegisterListenersProcessor::TAG}).
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
        $containerBuilder->processor(new RegisterListenersProcessor());
        $containerBuilder->processor(new RegisterSubscribersProcessor());
        $containerBuilder->configureInstanceOf(EventSubscriberInterface::class, function (ServiceBuilder $service) {
            $service->tag(EventSubscriberInterface::class);
        });

        $containerBuilder->register(EventDispatcher::class, [
            new Reference(ContainerInterface::class),
            [], // Listeners are resolved by the compiler pass
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        $containerBuilder->alias(EventDispatcherInterface::class, EventDispatcher::class);
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }
}
