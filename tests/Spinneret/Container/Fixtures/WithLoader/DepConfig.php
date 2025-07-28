<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader;

final readonly class DepConfig
{
    public function __construct(
        public string $key,
    ) {}
}
