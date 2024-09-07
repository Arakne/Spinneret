<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\Login;

use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;

readonly class LoginResponse
{
    public function __construct(
        public TestUser $authenticatedUser,
    ) {
    }
}
