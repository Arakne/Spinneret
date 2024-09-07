<?php

namespace Arakne\Spinneret\ValueObject;

use JsonSerializable;
use Override;
use TypeError;

use function is_int;

/**
 * Base value object type for integer values.
 *
 * It's advisable to not use this class directly, but to extend it and add domain-specific validation.
 *
 * @implements ValueObjectInterface<int>
 * @psalm-immutable
 * @psalm-consistent-constructor
 */
readonly class IntegerValueObject implements ValueObjectInterface, JsonSerializable
{
    protected function __construct(public int $value)
    {
        // No body needed
    }

    #[Override]
    final public function value(): int
    {
        return $this->value;
    }

    #[Override]
    final public function __toString(): string
    {
        return (string) $this->value;
    }

    #[Override]
    final public function jsonSerialize(): int
    {
        return $this->value;
    }

    #[Override]
    final public function equals(ValueObjectInterface $other): bool
    {
        /** @psalm-suppress NoInterfaceProperties */
        return $other::class === static::class && $this->value === $other->value;
    }

    #[Override]
    public static function from(mixed $value): static
    {
        if (!is_int($value)) {
            throw new InvalidPrimitiveTypeError(static::class, 'int', $value);
        }

        return new static($value);
    }

    #[Override]
    public static function tryFrom(mixed $value): ?static
    {
        if (!is_int($value)) {
            return null;
        }

        try {
            return static::from($value);
        } catch (ValueObjectException|TypeError) {
            return null;
        }
    }
}
