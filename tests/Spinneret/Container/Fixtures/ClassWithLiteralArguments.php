<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class ClassWithLiteralArguments
{
    public function __construct(
        public string $foo,
        public int $bar,
    ) {}
}
