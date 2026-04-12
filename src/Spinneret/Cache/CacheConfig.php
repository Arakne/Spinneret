<?php

namespace Arakne\Spinneret\Cache;

use Arakne\Spinneret\Cache\Driver\CacheDriverInterface;
use Arakne\Spinneret\Cache\Driver\NullCache;

/**
 * Configuration of the cache module.
 */
final readonly class CacheConfig
{
    public function __construct(
        /**
         * The cache driver class to use. Must implement CacheDriverInterface.
         *
         * @var class-string<CacheDriverInterface>
         */
        public string $driver = NullCache::class,

        /**
         * The namespace to prefix all cache keys with.
         * Useful to avoid key collisions when using the same cache for multiple applications.
         *
         * This parameter may not be supported by all cache drivers.
         * If the driver does not support namespaces, this parameter will be ignored.
         */
        public string $namespace = '',

        /**
         * Define specific cache configurations for different service.
         *
         * The key is the service ID as defined in the container,
         * and the value is a CacheConfig instance that will override the default configuration for that service.
         *
         * The key can be the exact service ID, or its interface or parent class.
         *
         * Caution: The overridden mapping and driver is defined as compile time, so any change to this configuration
         *          must be followed by cache clearance to take effect.
         *
         * @var array<string, CacheConfig>
         */
        public array $overrides = [],
    ) {}
}
