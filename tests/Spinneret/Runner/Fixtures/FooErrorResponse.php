<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

readonly class FooErrorResponse
{
    public function __construct(
        public string $message,
    ) {
    }
}
