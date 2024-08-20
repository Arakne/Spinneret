<?php

namespace Arakne\Tests\Spinneret\Application\Config\Fixtures;

final readonly class PersonConfig
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?int $age = null,
    ) {
    }
}
