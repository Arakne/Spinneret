<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Argument\Call;

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
     * @param list<mixed> $arguments Arguments to pass to the factory. If an argument is an instance of {@see ArgumentInterface}, it will be resolved by the container.
     *
     * @return Call
     */
    public function call(array $arguments): Call
    {
        return new Call($this, $arguments);
    }
}
