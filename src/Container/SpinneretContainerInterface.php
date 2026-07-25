<?php

namespace Arakne\Spinneret\Container;

use Override;
use Psr\Container\ContainerInterface;

/**
 * Base type for PSR-11 containers used in Spinneret.
 *
 * This type must not be used directly, it's useful only for container setup / configuration.
 */
interface SpinneretContainerInterface extends ContainerInterface
{
    /**
     * @param string|class-string<T> $id The service ID. Can be a class name.
     * @return ($id is class-string<T> ? T|null : mixed)
     * @template T as object
     */
    #[Override]
    public function get(string $id): mixed;

    /**
     * Set a service in the container.
     * The service should be registered on the builder before with `runtime` flag set to `true`.
     *
     * Be aware that this method does not check if the service is already registered, set, or used.
     * So you can overwrite existing services, which can lead to inconsistent state if called after
     * the service has been used has dependency in another service.
     *
     * @param string $id The service ID. Can be a class name.
     * @param mixed $value The service value to set.
     *
     * @return void
     */
    public function set(string $id, mixed $value): void;

    /**
     * Find all services tagged with the given tag.
     *
     * @param string $tag
     * @return iterable<array-key, mixed>
     */
    public function findByTag(string $tag): iterable;
}
