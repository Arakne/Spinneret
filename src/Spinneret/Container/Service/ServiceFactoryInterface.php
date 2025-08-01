<?php

namespace Arakne\Spinneret\Container\Service;

use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Interface for service factories.
 */
interface ServiceFactoryInterface
{
    /**
     * Call the factory to create a service instance.
     *
     * @param ContainerInterface $container The container instance.
     * @param list<mixed> $arguments Arguments to pass to the factory. Arguments must be resolved before calling this method.
     *
     * @return mixed
     */
    public function create(ContainerInterface $container, array $arguments): mixed;

    /**
     * Try to return the parameters of the service factory.
     * This method returns null if the parameters cannot be determined (the factory is dynamically created, for example).
     *
     * @return list<ReflectionParameter>|null
     */
    public function parameters(): ?array;

    /**
     * Compile the factory into a PHP code expression.
     *
     * @param string $arguments Arguments list to pass to the factory, as a string.
     * @return string
     */
    public function compile(string $arguments): string;
}
