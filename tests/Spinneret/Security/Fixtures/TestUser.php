<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures;

final readonly class TestUser
{
    public function __construct(
        public string $username,
        public string $password,
    ) {
    }
}
