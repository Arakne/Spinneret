<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

use function strtoupper;

class StaticFactory
{
    public static function create(string $value): SingleLiteralClass
    {
        return new SingleLiteralClass(strtoupper($value));
    }
}
