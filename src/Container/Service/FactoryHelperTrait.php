<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\FirstClassCallable;

/**
 * Add helper methods to service factories.
 *
 * @phpstan-require-implements ServiceFactoryInterface
 * @psalm-require-implements ServiceFactoryInterface
 */
trait FactoryHelperTrait
{
    /**
     * Convert the factory to a {@see Call} value, which can be used as argument.
     *
     * @param list<mixed> $arguments Arguments to pass to the factory. If an argument is an instance of {@see ValueInterface}, it will be resolved by the container.
     *
     * @return Call
     */
    public function call(array $arguments): Call
    {
        return new Call($this, $arguments);
    }

    /**
     * Convert the factory to a {@see FirstClassCallable} value, which can be used as a first-class callable.
     * This is useful for passing the factory as an argument to another function or method.
     *
     * @return FirstClassCallable
     */
    public function fcc(): FirstClassCallable
    {
        return new FirstClassCallable($this);
    }
}
