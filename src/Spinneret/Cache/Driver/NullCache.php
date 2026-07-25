<?php

namespace Arakne\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\CacheConfig;
use Override;
use Psr\Container\ContainerInterface;

use function array_fill_keys;
use function iterator_to_array;

/**
 * Dummy cache driver that does not store any data.
 * Useful to disable caching without changing the code that uses the cache.
 */
final readonly class NullCache implements CacheDriverInterface
{
    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    #[Override]
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return true;
    }

    #[Override]
    public function delete(string $key): bool
    {
        return true;
    }

    #[Override]
    public function clear(): bool
    {
        return true;
    }

    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return array_fill_keys(iterator_to_array($keys), $default);
    }

    /**
     * @param iterable<string, mixed> $values
     */
    #[Override]
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        return true;
    }

    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    #[Override]
    public function has(string $key): bool
    {
        return false;
    }

    #[Override]
    public static function create(CacheConfig $config, ContainerInterface $container): static
    {
        return self::instance();
    }

    public static function instance(): self
    {
        /** @var self $instance */
        static $instance = new self();

        return $instance;
    }
}
