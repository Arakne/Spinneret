<?php

namespace Arakne\Spinneret\Bus;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Bus\Compiler\RegisterHandlersCompilerPass;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
