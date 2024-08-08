<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompilerInterface;
use Override;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Register services for the router
 *
 * Required services:
 * - {@see FormFactoryInterface} - can be provided by the {@see FormModule}
 * - {@see Application} - provided by the Application itself
 *
 * Provided services:
 * - {@see RouterInterface} - alias to {@see Router}
 * - {@see UrlMatcherInterface}
 * - {@see UrlMatcherLoaderInterface} - alias to {@see UrlMatcherLoader}
 * - {@see UrlMatcherCompilerInterface} - alias to {@see UrlMatcherCompiler}
 * - {@see RouteCollectionLoaderInterface} - alias to {@see RouteCollectionLoader}
 * - {@see RequestContext}
 *
 * Note: no services are marked as public, as they are not meant to be used directly.
 */
final class RouterModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(Router::class, Router::class)
            ->setArguments([
                new Reference(UrlMatcherInterface::class),
                new Reference(FormFactoryInterface::class),
            ])
        ;

        $containerBuilder->setAlias(RouterInterface::class, Router::class);

        $containerBuilder->register(UrlMatcherInterface::class)
            ->setFactory([new Reference(UrlMatcherLoaderInterface::class), 'load'])
            ->setArguments([
                new Reference(Application::class),
            ])
        ;

        $containerBuilder->register(UrlMatcherLoader::class, UrlMatcherLoader::class)
            ->setArguments([
                new Reference(RouteCollectionLoaderInterface::class),
                new Reference(RequestContext::class),
                new Reference(UrlMatcherCompilerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;
        $containerBuilder->setAlias(UrlMatcherLoaderInterface::class, UrlMatcherLoader::class);

        $containerBuilder->register(UrlMatcherCompiler::class, UrlMatcherCompiler::class); // @todo configure file name ?
        $containerBuilder->setAlias(UrlMatcherCompilerInterface::class, UrlMatcherCompiler::class);

        $containerBuilder->register(RouteCollectionLoader::class, RouteCollectionLoader::class);
        $containerBuilder->setAlias(RouteCollectionLoaderInterface::class, RouteCollectionLoader::class);

        $containerBuilder->register(RequestContext::class, RequestContext::class); // @todo configure
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
