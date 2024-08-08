<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

readonly class SimpleResponse
{
    public function __construct(
        public string $content,
    ) {
    }
}
