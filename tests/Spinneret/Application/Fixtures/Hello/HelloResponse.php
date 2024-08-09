<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Hello;

final class HelloResponse
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
