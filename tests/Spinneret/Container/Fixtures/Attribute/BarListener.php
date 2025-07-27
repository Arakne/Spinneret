<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Attribute;

#[EventListener('bar')]
class BarListener
{
    public function __invoke($bar): void
    {
    }
}
