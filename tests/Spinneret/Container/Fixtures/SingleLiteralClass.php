<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class SingleLiteralClass
{
    public function __construct(
        public string $value,
    ) {}
}
