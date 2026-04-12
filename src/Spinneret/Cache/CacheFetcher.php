<?php

namespace Arakne\Spinneret\Cache;

use Closure;
use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Wrap a PSR-16 cache implementation to provide a simpler interface for fetching values from the cache.
 */
final readonly class CacheFetcher
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    /**
     * Try to fetch a value from the cache using the provided key.
     *
     * If the value is not found in the cache, opr null, compute it using the provided closure and store it
     * in the cache with the specified TTL before returning it.
     *
     * Note: this implementation cannot store null values in the cache.
     *
     * @param string|list<string> $key The cache key to fetch. Can be a string or an array of strings for nested keys.
     * @param Closure():T $compute A closure that computes the value to cache if it is not already cached. The closure should return the value to cache.
     * @param int|DateInterval|null $ttl The time to live for the cached value. Can be an integer (number of seconds), a DateInterval, or null for no expiration.
     *
     * @return T
     * @template T
     */
    public function fetch(string|array $key, Closure $compute, int|DateInterval|null $ttl = null): mixed
    {
        $key = CacheKey::toCacheKey($key);

        /** @var T|null $value */
        $value = $this->cache->get($key);

        if ($value === null) {
            $value = $compute();
            $this->cache->set($key, $value, $ttl);
        }

        /** @var T */
        return $value;
    }
}
