<?php

namespace Arakne\Spinneret\Container\Argument;

use Psr\Container\ContainerInterface;

/**
 * Store an argument value for a service factory or constructor.
 * All arguments must be compilable to a PHP string.
 *
 * @todo rename to ValueInterface
 */
interface ArgumentInterface
{
    /**
     * Resolve the argument value using the provided container.
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    public function resolve(ContainerInterface $container): mixed;

    /**
     * Compile the argument to a string representation.
     * Use "$this" to access the container instance within the compiled string.
     *
     * @return string
     */
    public function compile(): string;

    /**
     * Try to get the type of the argument.
     * The type should be the type of the actual value, and not the expected type of the factory or constructor argument.
     *
     * Note: this value is not cached, so it may be expensive to compute. Save the value if you need to use it multiple times.
     *
     * @return string|null The type of the argument, or null if it cannot be determined. If the type is an object, it should be the class name.
     */
    public function type(): ?string;
}
