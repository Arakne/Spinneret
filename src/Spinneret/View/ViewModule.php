<?php

namespace Arakne\Spinneret\View;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\Compiler\RegisterViewRenderersCompilerPass;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Module for the view engine
 *
 * Required services:
 * - {@see ResponseFactoryInterface}
 * - {@see StreamFactoryInterface}
 * - {@see PresenterDispatcherInterface} (to use forwarder)
 *
 * Provided services:
 * - {@see ViewEngineInterface} - alias to {@see Engine}
 * - {@see ForwarderInterface} - alias to {@see DispatcherForwarder}
 *
 * Used tags:
 * - ViewRendererInterface::class - to register view renderers. The attribute `response` will define the response class name that the renderer will handle.
 */
final class ViewModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new RegisterViewRenderersCompilerPass());

        $containerBuilder->register(Engine::class, Engine::class)
            ->setArguments([
                new Reference('service_container'),
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(TranslatorInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(ViewLocaleResolverInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new AbstractArgument('Defined by ' . RegisterViewRenderersCompilerPass::class),
            ])
        ;

        $containerBuilder->register(DispatcherForwarder::class, DispatcherForwarder::class)
            ->setArguments([
                new Reference(PresenterDispatcherInterface::class),
                new Reference(ViewEngineInterface::class),
            ])
        ;

        $containerBuilder->setAlias(ViewEngineInterface::class, Engine::class);
        $containerBuilder->setAlias(ForwarderInterface::class, DispatcherForwarder::class);
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void {}
}
