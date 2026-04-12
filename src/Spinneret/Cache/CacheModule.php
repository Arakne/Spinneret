<?php

namespace Arakne\Spinneret\Cache;

use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Cache\Driver\CacheDriverInterface;
use Arakne\Spinneret\Cache\Processor\RegisterOverriddenCacheProcessor;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Override;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache module for Spinneret
 *
 * @implements ConfigurableModuleInterface<CacheConfig>
 */
final readonly class CacheModule implements ConfigurableModuleInterface
{
    public function __construct(
        private CacheConfig $config = new CacheConfig(),
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
        $containerBuilder->processor(new RegisterOverriddenCacheProcessor($this->config));

        $containerBuilder->register(CacheDriverInterface::class)
            ->factory(new StaticMethodServiceFactory(new PropertyAccess(new Reference(CacheConfig::class), 'driver'), 'create'))
            ->arg(new Reference(CacheConfig::class))
            ->arg(new Reference(ContainerInterface::class))
        ;

        $containerBuilder->register(CacheFetcher::class, [new Reference(CacheInterface::class)]);

        $containerBuilder->alias(CacheInterface::class, CacheDriverInterface::class);
    }
}
