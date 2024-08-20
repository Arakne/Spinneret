<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures;

final readonly class Bar
{
    public function __construct(
        public Foo $foo
    ) {
    }
}
