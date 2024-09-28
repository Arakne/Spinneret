<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Application\CompilerPass\RegisterMiddlewareCompilerPass;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Runner\Runner;
use Arakne\Spinneret\Runner\RunnerInterface;
use Arakne\Spinneret\View\ViewEngineInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Base module for a Spinneret application
 *
 * @todo RunnerModule ?
 */
final readonly class ApplicationModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new RegisterMiddlewareCompilerPass(Runner::class));

        $containerBuilder->register(Application::class)->setPublic(true)->setSynthetic(true);

        $containerBuilder->register(Runner::class, Runner::class)
            ->setArguments([
                new Reference(RouterInterface::class),
                new Reference(PresenterDispatcherInterface::class),
                new Reference(ViewEngineInterface::class),
                new AbstractArgument('Should be defined by RegisterMiddlewareCompilerPass'),
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        $containerBuilder->setAlias(RunnerInterface::class, Runner::class)->setPublic(true);

        // @todo backend module ?
        $containerBuilder->setAlias(ResponseFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->setAlias(StreamFactoryInterface::class, Psr17Factory::class);

        $containerBuilder->register(Psr17Factory::class, Psr17Factory::class);
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
