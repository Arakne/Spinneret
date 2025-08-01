<?php

namespace Arakne\Spinneret\Logger;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Argument\ArgumentInterface;
use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Logger\Driver\FileLogger;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Closure;
use InvalidArgumentException;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function sprintf;
use function var_export;

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
            $filter = $this->createFilter($logger, $id, $channel);

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

        // @todo allow runtime configuration of the logger (i.e. use property accessor)
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

    private function createFilter(ArgumentInterface $logger, string|int $id, LogChannel $channel): ArgumentInterface
    {
        return new class($logger, $id) implements ArgumentInterface {
            public function __construct(
                private readonly ArgumentInterface $logger,
                private readonly string|int $id,
            ) {}

            #[Override]
            public function resolve(ContainerInterface $container): mixed
            {
                $config = $container->get(LoggerConfiguration::class)->channels[$this->id];
                assert($config instanceof LogChannel);

                return new LoggerFilter(
                    $this->logger->resolve($container),
                    LoggerFilter::levelToInt($config->minLevel),
                    LoggerFilter::levelToInt($config->maxLevel),
                    $config->contextKeys,
                    $config->filter,
                );
            }

            #[Override]
            public function compile(): string
            {
                // @todo optimize to get config once
                return sprintf('new \%s(%s, %s, %s, %s, %s)',
                    LoggerFilter::class,
                    $this->logger->compile(),
                    sprintf('\%s::levelToInt($this->get(%s)->channels[%s]->minLevel)', LoggerFilter::class, var_export(LoggerConfiguration::class, true), var_export($this->id, true)),
                    sprintf('\%s::levelToInt($this->get(%s)->channels[%s]->maxLevel)', LoggerFilter::class, var_export(LoggerConfiguration::class, true), var_export($this->id, true)),
                    sprintf('$this->get(%s)->channels[%s]->contextKeys', var_export(LoggerConfiguration::class, true), var_export($this->id, true)),
                    sprintf('$this->get(%s)->channels[%s]->filter', var_export(LoggerConfiguration::class, true), var_export($this->id, true)),
                );
            }

            #[Override]
            public function type(): ?string
            {
                return LoggerFilter::class;
            }
        };
    }
}
