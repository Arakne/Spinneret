<?php

namespace Arakne\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\CacheConfig;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Extends the PSR-16 simple cache interface to be used by the spinneret cache module.
 */
interface CacheDriverInterface extends CacheInterface
{
    /**
     * Create a new cache driver instance based on the provided configuration.
     *
     * @param CacheConfig $config
     * @param ContainerInterface $container
     *
     * @return static
     */
    public static function create(CacheConfig $config, ContainerInterface $container): static;
}
