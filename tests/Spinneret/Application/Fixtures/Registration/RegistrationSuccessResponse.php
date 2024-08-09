<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\User;

final readonly class RegistrationSuccessResponse
{

    public function __construct(public User $user)
    {
    }
}
