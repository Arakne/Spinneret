<?php

namespace Arakne\Spinneret\Logger;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Argument\ArgumentInterface;
use Arakne\Spinneret\Container\Argument\Call;
use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Logger\Driver\FileLogger;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;

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
        $loggerDispatcher = $containerBuilder->register(LoggerDispatcher::class);

        foreach ($this->configuration->channels as $id => $channel) {
            $logger = $this->createLogger($channel);
            $filter = $this->createFilter($logger, $id);

            $loggerDispatcher->arg($filter);
        }

        $containerBuilder->alias(LoggerInterface::class, LoggerDispatcher::class);
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    private function createLogger(LogChannel $channel): ArgumentInterface
    {
        if ($channel->service !== null) {
            return new Reference($channel->service);
        }

        if ($channel->file !== null) {
            return new Literal(
                new FileLogger(
                    $channel->file,
                    $channel->bufferSize ?? FileLogger::DEFAULT_BUFFER_SIZE
                )
            );
        }

        throw new InvalidArgumentException('Either file or service must be set');
    }

    private function createFilter(ArgumentInterface $logger, string|int $id): ArgumentInterface
    {
        $channelConfig = new Reference(LoggerConfiguration::class)->property('channels')->offset($id);

        return new Call(
            self::createFilterFromChannel(...),
            [$logger, $channelConfig]
        );
    }

    /**
     * @internal Used by container
     */
    public static function createFilterFromChannel(LoggerInterface $logger, LogChannel $channel): LoggerFilter
    {
        return new LoggerFilter(
            $logger,
            LoggerFilter::levelToInt($channel->minLevel),
            LoggerFilter::levelToInt($channel->maxLevel),
            $channel->contextKeys,
            $channel->filter
        );
    }
}
