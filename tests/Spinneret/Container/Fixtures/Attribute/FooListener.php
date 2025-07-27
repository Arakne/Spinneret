<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Attribute;

#[EventListener('foo')]
class FooListener
{
    public function __invoke($foo): void
    {
    }
}
