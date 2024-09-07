<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

readonly class FooCommand
{
    public function __construct(
        public int $value,
    ) {}
}
