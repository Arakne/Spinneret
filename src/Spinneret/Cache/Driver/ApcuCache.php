<?php

namespace Arakne\Spinneret\Cache\Driver;

use APCUIterator;
use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Cache\CacheKey;
use Arakne\Spinneret\Cache\Exception\UnsupportedDriverException;
use DateInterval;
use Override;
use Psr\Container\ContainerInterface;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_exists;
use function apcu_fetch;
use function apcu_store;
use function array_key_exists;
use function array_keys;
use function extension_loaded;
use function is_array;
use function iterator_to_array;
use function strlen;
use function substr;

/**
 * Cache driver that uses the APCu extension for in-memory caching.
 */
final readonly class ApcuCache implements CacheDriverInterface
{
    public function __construct(
        /**
         * Prefix to prepend to all cache keys.
         * This is used as a namespace to avoid collisions with other applications using the same APCu cache.
         */
        private string $prefix = '',
    ) {
        if (!extension_loaded('apcu')) {
            throw new UnsupportedDriverException('apcu', 'The APCu extension is not loaded.');
        }
    }

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefix . $key;
        CacheKey::assertValidKey($key);

        /** @var mixed $value */
        $value = apcu_fetch($key, $success);

        if (!$success) {
            return $default;
        }

        return $value;
    }

    #[Override]
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = $ttl->s + $ttl->i * 60 + $ttl->h * 3600 + $ttl->d * 86400 + $ttl->m * 2592000 + $ttl->y * 31536000;
        }

        if ($ttl !== null && $ttl <= 0) {
            return $this->delete($key);
        }

        $key = $this->prefix . $key;
        CacheKey::assertValidKey($key);

        return apcu_store($key, $value, $ttl ?? 0);
    }

    #[Override]
    public function delete(string $key): bool
    {
        $key = $this->prefix . $key;
        CacheKey::assertValidKey($key);

        return apcu_delete($key);
    }

    #[Override]
    public function clear(): bool
    {
        if ($this->prefix === '') {
            return apcu_clear_cache();
        }

        apcu_delete(new APCUIterator('/^' . preg_quote($this->prefix) . '/'));

        return true;
    }

    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $toRetrieve = [];

        foreach ($keys as $key) {
            $key = $this->prefix . $key;
            CacheKey::assertValidKey($key);
            $toRetrieve[] = $key;
        }

        $stored = apcu_fetch($toRetrieve);

        if (!is_array($stored)) {
            $stored = [];
        }

        $prefixLen = strlen($this->prefix);

        foreach ($toRetrieve as $key) {
            /** @var mixed $value */
            $value = array_key_exists($key, $stored) ? $stored[$key] : $default;

            yield substr($key, $prefixLen) => $value;
        }
    }

    /**
     * @param iterable<string, mixed> $values
     */
    #[Override]
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = $ttl->s + $ttl->i * 60 + $ttl->h * 3600 + $ttl->d * 86400 + $ttl->m * 2592000 + $ttl->y * 31536000;
        }

        if ($ttl !== null && $ttl <= 0) {
            /** @psalm-suppress PossiblyInvalidArgument */
            return $this->deleteMultiple(array_keys(iterator_to_array($values)));
        }

        /**
         * @var array<string, mixed> $toStore
         */
        $toStore = [];

        /**
         * @var string $key
         * @var mixed $value
         */
        foreach ($values as $key => $value) {
            $key = $this->prefix . $key;
            CacheKey::assertValidKey($key);

            $toStore[$key] = $value;
        }

        apcu_store($toStore, null, $ttl ?? 0);

        return true;
    }

    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $toDelete = [];

        foreach ($keys as $key) {
            $key = $this->prefix . $key;
            CacheKey::assertValidKey($key);
            $toDelete[] = $key;
        }

        apcu_delete($toDelete);

        return true;
    }

    #[Override]
    public function has(string $key): bool
    {
        $key = $this->prefix . $key;
        CacheKey::assertValidKey($key);

        return apcu_exists($key);
    }

    #[Override]
    public static function create(CacheConfig $config, ContainerInterface $container): static
    {
        return new self($config->namespace);
    }
}
