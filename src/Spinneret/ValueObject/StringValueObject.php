<?php

namespace Arakne\Spinneret\ValueObject;

use JsonSerializable;
use Override;
use TypeError;

/**
 * @implements ValueObjectInterface<string>
 * @psalm-immutable
 */
abstract readonly class StringValueObject implements ValueObjectInterface, JsonSerializable
{
    protected function __construct(
        public string $value,
    ) {
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
    final public function __toString(): string
    {
        return $this->value;
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
