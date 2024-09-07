<?php

namespace Arakne\Spinneret\ValueObject;

/**
 * Base type for value objects.
 * Implementations must be immutable.
 *
 * @template T
 * @psalm-immutable
 */
interface ValueObjectInterface
{
    /**
     * Get the internal primitive value
     *
     * @return T
     */
    public function value(): mixed;

    /**
     * Convert the value object to a displayable string
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Check if the value object is equal to another value object
     * Two value objects are equals if they have the class and the same value
     *
     * A basic implementation can be: `return $this->value() === $other->value() && $other instanceof static;`
     *
     * @param ValueObjectInterface $other
     * @return bool
     */
    public function equals(self $other): bool;

    /**
     * Convert the primitive value to a value object
     * If the value is not valid, an exception must be thrown
     *
     * @param mixed $value
     * @return static
     *
     * @throws InvalidPrimitiveTypeError if the type $value is not valid
     * @throws InvalidValueException if the value $value is not valid (e.g. out of range or invalid format)
     */
    public static function from(mixed $value): static;

    /**
     * Convert the primitive value to a value object
     * This is a fail-safe version of from() : if the value is not valid, null is returned
     *
     * @param mixed $value The value to convert
     *
     * @return static|null The value object or null if the value is not valid
     */
    public static function tryFrom(mixed $value): ?static;
}
