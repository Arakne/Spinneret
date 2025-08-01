<?php

namespace Arakne\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Service\MethodServiceFactory;

/**
 * Add utilities to create derived arguments from a value.
 *
 * @phpstan-require-implements ArgumentInterface
 * @psalm-require-implements ArgumentInterface
 */
trait ValueHelperTrait
{
    /**
     * Create a factory method from the referenced service and the given name.
     *
     * @param string $name The method name to call on the referenced service.
     * @return MethodServiceFactory
     */
    public function method(string $name): MethodServiceFactory
    {
        return new MethodServiceFactory($this, $name);
    }

    /**
     * Create a property access from the referenced service and the given property name.
     *
     * @param string $property The name of the property to access on the referenced service.
     * @return PropertyAccess
     */
    public function property(string $property): PropertyAccess
    {
        return new PropertyAccess($this, $property);
    }

    /**
     * Create an ArrayOffset from the referenced service and the offset.
     *
     * @param int|string $offset
     * @return ArrayOffset
     */
    public function offset(int|string $offset): ArrayOffset
    {
        return new ArrayOffset($this, $offset);
    }

    /**
     * Wrap the current value in a Closure to defer its resolution.
     *
     * @return ClosureArgument
     */
    public function asClosure(): ClosureArgument
    {
        return new ClosureArgument($this);
    }
}
