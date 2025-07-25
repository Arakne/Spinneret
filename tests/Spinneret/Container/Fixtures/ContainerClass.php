<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class ContainerClass
{
    public function __construct(
        public SimpleClass $simpleClass,
        public ClassWithLiteralArguments $classWithLiteralArguments,
    ) {}
}
