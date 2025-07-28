<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages;

final readonly class DoB
{
    public function __construct(
        public string $name,
    ) {}
}
