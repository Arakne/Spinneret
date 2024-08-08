<?php

namespace Arakne\Tests\Spinneret\Fixtures\Hello;

final class HelloResponse
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
