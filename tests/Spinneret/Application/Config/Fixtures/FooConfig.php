<?php

namespace Arakne\Tests\Spinneret\Application\Config\Fixtures;

final readonly class FooConfig
{
    public function __construct(
        public string $foo = 'bar',
    ) {
    }
}
