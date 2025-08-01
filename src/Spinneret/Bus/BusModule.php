<?php

namespace Arakne\Spinneret\Bus;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Bus\Processor\RegisterHandlersProcessor;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Module for register the command bus dispatcher.
 *
 * This module will resolve the handlers from the container, using the tag "spinneret.bus.handler" (cf: {@see RegisterHandlersProcessor::TAG}).
 * To handled message can be explicitly defined using the "message" attribute on the tag, or it will be resolved from the argument of the handler.
 *
 * Optional services:
 * - {@see LoggerInterface} - to enable logging of dispatched messages
 *
 * Provided services:
 * - {@see BusDispatcherInterface} - alias to {@see BusDispatcher}
 */
final readonly class BusModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->processor(new RegisterHandlersProcessor());

        $containerBuilder->register(BusDispatcher::class, [
            new Reference(ContainerInterface::class),
            [],
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        $containerBuilder->alias(BusDispatcherInterface::class, BusDispatcher::class);
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }
}
