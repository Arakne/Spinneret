<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader;

final readonly class SimpleDep
{
    public function __construct(
        public DepConfig $config,
    ) {}
}
