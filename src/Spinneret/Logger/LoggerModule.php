<?php

namespace Arakne\Spinneret\Logger;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Logger\Driver\FileLogger;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Closure;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Module for enabling logging.
 *
 * Provided services:
 * - {@see LoggerInterface} - Alias to {@see LoggerDispatcher}.
 *
 * @implements ConfigurableModuleInterface<LoggerConfiguration>
 */
final readonly class LoggerModule implements ConfigurableModuleInterface
{
    public function __construct(
        private LoggerConfiguration $configuration = new LoggerConfiguration(),
    ) {}

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->configuration;
    }

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $loggerDispatcher = $containerBuilder->register(LoggerDispatcher::class, LoggerDispatcher::class);

        foreach ($this->configuration->channels as $id => $channel) {
            $logger = $this->createLogger($channel);
            $filter = $this->createFilter($logger, $id, $channel);

            $loggerDispatcher->addArgument($filter);
        }

        $containerBuilder->setAlias(LoggerInterface::class, LoggerDispatcher::class);
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

    private function createLogger(LogChannel $channel): Definition|Reference
    {
        if ($channel->service !== null) {
            return new Reference($channel->service);
        }

        if ($channel->file !== null) {
            $logger = new Definition(FileLogger::class);
            $logger->addArgument($channel->file);

            if ($channel->bufferSize !== null) {
                $logger->addArgument($channel->bufferSize);
            }

            return $logger;
        }

        throw new InvalidArgumentException('Either file or service must be set');
    }

    private function createFilter(Definition|Reference $logger, string|int $id, LogChannel $channel): Definition
    {
        $filter = new Definition(LoggerFilter::class);
        $filter->setArgument('$logger', $logger);

        if ($channel->minLevel !== null) {
            $filter->setArgument('$levelMin', LoggerFilter::levelToInt($channel->minLevel));
        }

        if ($channel->maxLevel !== null) {
            $filter->setArgument('$levelMax', LoggerFilter::levelToInt($channel->maxLevel));
        }

        if ($channel->contextKeys) {
            $filter->setArgument('$contextKeys', $channel->contextKeys);
        }

        if ($channel->filter !== null) {
            $filter->setArgument(
                '$filter',
                (new Definition(Closure::class))
                    ->setFactory([new Reference(LoggerConfiguration::class), 'getFilter'])
                    ->setArguments([$id])
            );
        }

        return $filter;
    }
}
