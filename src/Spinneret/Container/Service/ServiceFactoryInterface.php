<?php

namespace Arakne\Spinneret\Container\Service;

use Psr\Container\ContainerInterface;
use ReflectionParameter;

interface ServiceFactoryInterface
{
    public function create(ContainerInterface $container, array $arguments): mixed;

    /**
     * Try to return the parameters of the service factory.
     * This method returns null if the parameters cannot be determined (the factory is dynamically created, for example).
     *
     * @return list<ReflectionParameter>|null
     */
    public function parameters(): ?array;

    public function compile(string $arguments): string;
}
