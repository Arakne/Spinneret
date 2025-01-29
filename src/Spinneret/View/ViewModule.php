<?php

namespace Arakne\Spinneret\View;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
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
 * Required parameters:
 * - spinneret.renderers - Associative array of response class name to view renderer class name (prefer use constant {@see ViewModule::RENDERERS_PARAMETER})
 *
 * Provided services:
 * - {@see ViewEngineInterface} - alias to {@see Engine}
 * - {@see ForwarderInterface} - alias to {@see DispatcherForwarder}
 */
final class ViewModule implements ModuleInterface
{
    public const string RENDERERS_PARAMETER = 'spinneret.renderers';

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(Engine::class, Engine::class)
            ->setArguments([
                new Reference('service_container'),
                new Reference(ResponseFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(TranslatorInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference(ViewLocaleResolverInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
                '%'.self::RENDERERS_PARAMETER.'%',
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
