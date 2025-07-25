<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class AutowireableFactory
{
    public function __construct(
        public ClassWithLiteralArguments $dependency,
    ) {}

    public function create(string $value): SingleLiteralClass
    {
        return new SingleLiteralClass($value . $this->dependency->foo);
    }
}
