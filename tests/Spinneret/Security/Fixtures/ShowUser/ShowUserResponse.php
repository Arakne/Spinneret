<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\ShowUser;

use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;

readonly class ShowUserResponse
{
    public function __construct(
        public TestUser $user,
    ) {
    }
}
