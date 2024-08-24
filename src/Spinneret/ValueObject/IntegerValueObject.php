<?php

namespace Arakne\Spinneret\ValueObject;

use Override;
use TypeError;

/**
 * Base value object type for integer values.
 *
 * @implements ValueObjectInterface<int>
 * @psalm-immutable
 */
readonly class IntegerValueObject implements ValueObjectInterface
{
    protected function __construct(
        public int $value,
    ) {
    }

    #[Override]
    public function value(): int
    {
        return $this->value;
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->value;
    }

    #[Override]
    public static function from(mixed $value): static
    {
        return new static($value);
    }

    #[Override]
    public static function tryFrom(mixed $value): ?static
    {
        try {
            return static::from($value);
        } catch (ValueObjectException|TypeError) {
            return null;
        }
    }
}
