<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

readonly class FooSuccessResponse
{
    public function __construct(
        public string $message,
    ) {
    }
}
