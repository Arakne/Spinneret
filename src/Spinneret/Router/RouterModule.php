<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompiler;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompilerInterface;
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
 * - {@see RouterConfig} - provided by the config system
 *
 * Provided services:
 * - {@see RouterInterface} - alias to {@see Router}
 * - {@see UrlMatcherInterface}
 * - {@see UrlMatcherLoaderInterface} - alias to {@see UrlMatcherLoader}
 * - {@see UrlMatcherCompilerInterface} - alias to {@see UrlMatcherCompiler}
 * - {@see UrlGeneratorInterface}
 * - {@see UrlGeneratorCompilerInterface} - alias to {@see UrlGeneratorCompiler}
 * - {@see UrlGeneratorLoaderInterface} - alias to {@see UrlGeneratorLoader}
 * - {@see RouteCollectionLoaderInterface} - alias to {@see RouteCollectionLoader}
 * - {@see RequestContext}
 *
 * Note: no services are marked as public, as they are not meant to be used directly.
 *
 * @implements ConfigurableModuleInterface<RouterConfig>
 */
final readonly class RouterModule implements ConfigurableModuleInterface
{
    public function __construct(
        private RouterConfig $config = new RouterConfig(),
    ) {}

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new static($configuration);
    }

    #[Override]
    public function configuration(): RouterConfig
    {
        return $this->config;
    }

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

        $containerBuilder->register(UrlGeneratorInterface::class, UrlGeneratorInterface::class)
            ->setFactory([new Reference(UrlGeneratorLoaderInterface::class), 'load'])
            ->setArguments([
                new Reference(Application::class),
            ])
        ;
        $containerBuilder->register(UrlGeneratorLoader::class, UrlGeneratorLoader::class)
            ->setArguments([
                new Reference(RouteCollectionLoaderInterface::class),
                new Reference(RequestContext::class),
                new Reference(UrlGeneratorCompilerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;
        $containerBuilder->setAlias(UrlGeneratorLoaderInterface::class, UrlGeneratorLoader::class);

        $containerBuilder->register(UrlMatcherCompiler::class, UrlMatcherCompiler::class); // @todo configure file name ?
        $containerBuilder->setAlias(UrlMatcherCompilerInterface::class, UrlMatcherCompiler::class);

        $containerBuilder->register(UrlGeneratorCompiler::class, UrlGeneratorCompiler::class); // @todo configure file name ?
        $containerBuilder->setAlias(UrlGeneratorCompilerInterface::class, UrlGeneratorCompiler::class);

        $containerBuilder->register(RouteCollectionLoader::class, RouteCollectionLoader::class);
        $containerBuilder->setAlias(RouteCollectionLoaderInterface::class, RouteCollectionLoader::class);

        $containerBuilder->register(RequestContext::class, RequestContext::class)
            ->setFactory([self::class, 'createRequestContext'])
            ->setArguments([new Reference(RouterConfig::class)])
        ;
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    public static function createRequestContext(RouterConfig $config): RequestContext
    {
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        return $config->baseUrl ? RequestContext::fromUri($config->baseUrl) : new RequestContext();
    }
}
