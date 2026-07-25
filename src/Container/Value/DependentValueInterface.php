<?php

namespace Arakne\Spinneret\Container\Value;

/**
 * A value argument that depends on other services.
 * Unlike {@see NestedValueInterface}, dependencies cannot be inlined or modified during optimizations.
 */
interface DependentValueInterface extends ValueInterface
{
    /**
     * Get the list of service IDs or aliases this value depends on.
     *
     * @return list<string>
     */
    public function dependencies(): array;
}
