<?php

namespace Arakne\Spinneret\ValueObject;

use TypeError;

use function get_debug_type;
use function sprintf;

/**
 * Exception thrown when a primitive type is invalid for a value object.
 */
class InvalidPrimitiveTypeError extends TypeError implements ValueObjectException
{
    public function __construct(string $valueObjectClass, string $expected, mixed $value)
    {
        parent::__construct(sprintf(
            'Invalid primitive type for %s. Expected %s, got %s',
            $valueObjectClass,
            $expected,
            get_debug_type($value),
        ));
    }
}
