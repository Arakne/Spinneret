<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

final readonly class AccessDenied
{
    public function __construct(
        public ?string $message = null,
    ) {}
}
