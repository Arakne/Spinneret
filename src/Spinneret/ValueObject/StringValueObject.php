<?php

namespace Arakne\Spinneret\ValueObject;

use JsonSerializable;
use Override;
use TypeError;

use function is_string;

/**
 * Base value object type for string values.
 *
 * It's advisable to not use this class directly, but to extend it and add domain-specific validation.
 *
 * @implements ValueObjectInterface<string>
 * @psalm-immutable
 * @psalm-consistent-constructor
 */
readonly class StringValueObject implements ValueObjectInterface, JsonSerializable
{
    protected function __construct(public string $value)
    {
        // No body needed
    }

    #[Override]
    final public function jsonSerialize(): string
    {
        return $this->value;
    }

    #[Override]
    final public function value(): string
    {
        return $this->value;
    }

    #[Override]
    public function equals(ValueObjectInterface $other): bool
    {
        /** @psalm-suppress NoInterfaceProperties */
        return $other::class === static::class && $this->value === $other->value;
    }

    #[Override]
    final public function __toString(): string
    {
        return $this->value;
    }

    #[Override]
    public static function from(mixed $value): static
    {
        if (!is_string($value)) {
            throw new InvalidPrimitiveTypeError(static::class, 'string', $value);
        }

        return new static($value);
    }

    #[Override]
    public static function tryFrom(mixed $value): ?static
    {
        if (!is_string($value)) {
            return null;
        }

        try {
            return static::from($value);
        } catch (ValueObjectException|TypeError) {
            return null;
        }
    }
}
