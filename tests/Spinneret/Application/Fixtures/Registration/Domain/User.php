<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain;

final readonly class User
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ){
    }
}
