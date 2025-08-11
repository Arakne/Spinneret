<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Arakne\Spinneret\Container\Value\ValueInterface;

/**
 * Interface for objects that can be validated within a container builder.
 * It can be used by {@see ServiceFactoryInterface} or {@see ValueInterface}.
 */
interface ValidatableInterface
{
    /**
     * Check if the current value is valid on the given container builder.
     *
     * The value should be considered as invalid if it has missing dependencies,
     * invalid configurations, or any other issues that would prevent it from being
     * properly instantiated or used within the container.
     *
     * @param ContainerBuilder $builder
     * @return bool
     */
    public function validate(ContainerBuilder $builder): bool;
}
