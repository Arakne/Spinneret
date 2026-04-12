<?php

namespace Arakne\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Time\SystemClock;
use DateInterval;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function assert;
use function is_int;
use function is_object;
use function is_string;

/**
 * In-memory cache driver implementation.
 */
final class MemoryCache implements CacheDriverInterface
{
    /**
     * Cache entries stored in memory.
     *
     * @var array<string, mixed>
     */
    private array $entries = [];

    /**
     * Array of expiration timestamps indexed by cache key.
     *
     * @var array<string, int|null>
     */
    private array $expirations = [];

    public function __construct(
        private readonly ClockInterface $clock = new SystemClock(),
    ) {}

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        /** @var mixed $value */
        $value = $this->entries[$key] ?? null;

        if ($value === null && !array_key_exists($key, $this->entries)) {
            return $default;
        }

        $expiration = $this->expirations[$key] ?? null;

        if ($expiration === null) {
            return is_object($value) ? clone $value : $value;
        }

        if ($this->clock->now()->getTimestamp() >= $expiration) {
            unset($this->entries[$key], $this->expirations[$key]);

            return $default;
        }

        return is_object($value) ? clone $value : $value;
    }

    #[Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl !== null) {
            if (
                (is_int($ttl) && $ttl <= 0)
                || ($ttl instanceof DateInterval && $ttl->invert)
            ) {
                unset($this->entries[$key], $this->expirations[$key]);
                return true;
            }
        }

        $this->entries[$key] = is_object($value) ? clone $value : $value;
        $this->expirations[$key] = $this->ttlToTimestamp($ttl);

        return true;
    }

    #[Override]
    public function delete(string $key): bool
    {
        unset($this->entries[$key], $this->expirations[$key]);

        return true;
    }

    #[Override]
    public function clear(): bool
    {
        $this->entries = [];
        $this->expirations = [];

        return true;
    }

    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $now = $this->clock->now()->getTimestamp();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->entries)) {
                yield $key => $default;
                continue;
            }

            /** @var mixed $value */
            $value = $this->entries[$key];
            $expiration = $this->expirations[$key] ?? null;

            if ($expiration !== null && $now >= $expiration) {
                unset($this->entries[$key], $this->expirations[$key]);
                yield $key => $default;
                continue;
            }

            yield $key => (is_object($value) ? clone $value : $value);
        }
    }

    #[Override]
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl !== null) {
            if (
                (is_int($ttl) && $ttl <= 0)
                || ($ttl instanceof DateInterval && $ttl->invert)
            ) {
                foreach ($values as $key => $_) {
                    assert(is_string($key));

                    unset($this->entries[$key], $this->expirations[$key]);
                }

                return true;
            }
        }

        $ttl = $this->ttlToTimestamp($ttl);

        /**
         * @var string $key
         * @var mixed $value
         */
        foreach ($values as $key => $value) {
            $this->entries[$key] = is_object($value) ? clone $value : $value;
            $this->expirations[$key] = $ttl;
        }

        return true;
    }

    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->entries[$key], $this->expirations[$key]);
        }

        return true;
    }

    #[Override]
    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->entries)) {
            return false;
        }

        $expiration = $this->expirations[$key] ?? null;

        if ($expiration === null) {
            return true;
        }

        if ($this->clock->now()->getTimestamp() >= $expiration) {
            unset($this->entries[$key], $this->expirations[$key]);
            return false;
        }

        return true;
    }

    private function ttlToTimestamp(DateInterval|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        $now = $this->clock->now();

        if (is_int($ttl)) {
            return $now->getTimestamp() + $ttl;
        }

        return $now->add($ttl)->getTimestamp();
    }

    #[Override]
    public static function create(CacheConfig $config, ContainerInterface $container): static
    {
        $clock = $container->has(ClockInterface::class) ? $container->get(ClockInterface::class) : new SystemClock();
        assert($clock instanceof ClockInterface);

        return new self($clock);
    }
}
