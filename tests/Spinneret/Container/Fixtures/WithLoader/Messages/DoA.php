<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages;

final readonly class DoA
{
    public function __construct(
        public int $value,
    ) {}
}
