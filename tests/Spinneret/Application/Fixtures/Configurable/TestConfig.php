<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

final readonly class TestConfig
{
    public function __construct(
        public string $message = '',
        public int $computed = 0,
    ) {
    }
}
