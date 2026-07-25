<?php

namespace Arakne\Spinneret\View;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\View\Processor\RegisterViewRenderersProcessor;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
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
        $containerBuilder->processor(new RegisterViewRenderersProcessor());

        $containerBuilder->register(Engine::class, [
            new Reference(ContainerInterface::class),
            new Reference(ResponseFactoryInterface::class),
            new Reference(StreamFactoryInterface::class),
            new Reference(TranslatorInterface::class, nullOnInvalid: true),
            new Reference(ViewLocaleResolverInterface::class, nullOnInvalid: true),
            new Reference(ViewThemeResolverInterface::class, nullOnInvalid: true),
            [], // renderers
            [], // theme renderers
        ]);

        $containerBuilder->register(DispatcherForwarder::class, [
            new Reference(PresenterDispatcherInterface::class),
            new Reference(ViewEngineInterface::class),
        ]);

        $containerBuilder->alias(ViewEngineInterface::class, Engine::class);
        $containerBuilder->alias(ForwarderInterface::class, DispatcherForwarder::class);
    }
}
