<?php

namespace Arakne\Spinneret\Runner;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Runner\Backend\Httpd\HttpdBackend;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanBackend;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanConfig;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanStartCommand;
use Arakne\Spinneret\Runner\CompilerPass\RegisterMiddlewareCompilerPass;
use Arakne\Spinneret\View\ViewEngineInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for register Runner and backend services
 *
 * Provided services:
 * - {@see RunnerInterface} - Alias to {@see Runner} (public)
 * - {@see ServerRequestFactoryInterface} - Alias to {@see Psr17Factory}
 * - {@see StreamFactoryInterface} - Alias to {@see Psr17Factory}
 * - {@see UriFactoryInterface} - Alias to {@see Psr17Factory}
 * - {@see UploadedFileFactoryInterface} - Alias to {@see Psr17Factory}
 * - {@see ResponseFactoryInterface} - Alias to {@see Psr17Factory}
 *
 * If {@see RunnerConfig::httpd} is true, also provides:
 * - {@see HttpdBackend} - Backend for handling requests from a web server (public)
 * - {@see ServerRequestCreatorInterface} - Alias to {@see ServerRequestCreator}
 *
 * @implements ConfigurableModuleInterface<RunnerConfig>
 */
final readonly class RunnerModule implements ConfigurableModuleInterface
{
    public function __construct(
        private RunnerConfig $config = new RunnerConfig(),
    ) {
    }

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->config;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new RegisterMiddlewareCompilerPass(Runner::class));

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

        $containerBuilder->register(Psr17Factory::class, Psr17Factory::class);
        $containerBuilder->setAlias(ResponseFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->setAlias(StreamFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->setAlias(ServerRequestFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->setAlias(UriFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->setAlias(UploadedFileFactoryInterface::class, Psr17Factory::class);

        if ($this->config->httpd) {
            $this->registerHttpdBackend($containerBuilder);
        }

        if ($this->config->workerman->enable) {
            $this->registerWorkermanBackend($containerBuilder);
        }
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

    private function registerHttpdBackend(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ServerRequestCreator::class, ServerRequestCreator::class)
            ->setArguments([
                new Reference(ServerRequestFactoryInterface::class),
                new Reference(UriFactoryInterface::class),
                new Reference(UploadedFileFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
            ])
        ;

        $containerBuilder->setAlias(ServerRequestCreatorInterface::class, ServerRequestCreator::class);

        $containerBuilder->register(HttpdBackend::class, HttpdBackend::class)
            ->setArguments([
                new Reference(Application::class),
                new Reference(ServerRequestCreatorInterface::class),
            ])
            ->setPublic(true)
        ;
    }

    private function registerWorkermanBackend(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(WorkermanConfig::class, WorkermanConfig::class)
            ->setFactory([new Reference(RunnerConfig::class), 'workerman'])
        ;

        $containerBuilder->register(WorkermanBackend::class, WorkermanBackend::class)
            ->setArguments([
                new Reference(Application::class),
                new Reference(WorkermanConfig::class),
            ])
            ->setPublic(true)
        ;

        $containerBuilder->register(WorkermanStartCommand::class, WorkermanStartCommand::class)
            ->setArguments([
                new Reference(WorkermanBackend::class),
            ])
            ->addTag(Command::class)
        ;
    }
}
