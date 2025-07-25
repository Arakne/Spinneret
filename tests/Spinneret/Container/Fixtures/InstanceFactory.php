<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class InstanceFactory
{
    public function __construct(
        public string $suffix,
    ) {}

    public function create(string $value): SingleLiteralClass
    {
        return new SingleLiteralClass($value . $this->suffix);
    }
}
