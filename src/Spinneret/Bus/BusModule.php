<?php

namespace Arakne\Spinneret\Bus;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Bus\Compiler\RegisterHandlersCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for register the command bus dispatcher.
 *
 * This module will resolve the handlers from the container, using the tag "spinneret.bus.handler" (cf: {@see RegisterHandlersCompilerPass::TAG}).
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
        $containerBuilder->addCompilerPass(new RegisterHandlersCompilerPass());

        $containerBuilder->register(BusDispatcher::class, BusDispatcher::class)
            ->setArguments([
                new Reference('service_container'),
                new AbstractArgument('Handlers must be injected using ' . RegisterHandlersCompilerPass::class),
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        $containerBuilder->setAlias(BusDispatcherInterface::class, BusDispatcher::class);
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
