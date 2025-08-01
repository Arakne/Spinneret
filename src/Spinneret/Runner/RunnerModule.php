<?php

namespace Arakne\Spinneret\Runner;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Runner\Backend\Httpd\HttpdBackend;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanBackend;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanConfig;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanStartCommand;
use Arakne\Spinneret\Runner\Processor\RegisterMiddlewareProcessor;
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
    ) {}

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
        $containerBuilder->processor(new RegisterMiddlewareProcessor(Runner::class));

        $containerBuilder->register(Runner::class, [
            new Reference(RouterInterface::class),
            new Reference(PresenterDispatcherInterface::class),
            new Reference(ViewEngineInterface::class),
            [],
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);

        $containerBuilder->alias(RunnerInterface::class, Runner::class);

        $containerBuilder->register(Psr17Factory::class);
        $containerBuilder->alias(ResponseFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->alias(StreamFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->alias(ServerRequestFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->alias(UriFactoryInterface::class, Psr17Factory::class);
        $containerBuilder->alias(UploadedFileFactoryInterface::class, Psr17Factory::class);

        if ($this->config->httpd) {
            $this->registerHttpdBackend($containerBuilder);
        }

        if ($this->config->workerman?->enable === true) {
            $this->registerWorkermanBackend($containerBuilder);
        }
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    private function registerHttpdBackend(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ServerRequestCreator::class, [
            new Reference(ServerRequestFactoryInterface::class),
            new Reference(UriFactoryInterface::class),
            new Reference(UploadedFileFactoryInterface::class),
            new Reference(StreamFactoryInterface::class),
        ]);

        $containerBuilder->alias(ServerRequestCreatorInterface::class, ServerRequestCreator::class);

        $containerBuilder->register(
            HttpdBackend::class,
            [
                new Reference(Application::class),
                new Reference(ServerRequestCreatorInterface::class),
            ]
        )->public = true;
    }

    private function registerWorkermanBackend(ContainerBuilder $containerBuilder): void
    {
        // @todo use value with ObjectProperty when available
        $containerBuilder->register(WorkermanConfig::class)
            ->factory(new Reference(RunnerConfig::class)->method('workerman'))
        ;

        $containerBuilder->register(WorkermanBackend::class, [
            new Reference(Application::class),
            new Reference(WorkermanConfig::class),
        ])->public = true;

        $containerBuilder->register(WorkermanStartCommand::class, [new Reference(WorkermanBackend::class)]);
    }
}
