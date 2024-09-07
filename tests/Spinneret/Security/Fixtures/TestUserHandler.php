<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures;

use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Override;

/**
 * @implements UserHandlerInterface<TestUser>
 */
class TestUserHandler implements UserHandlerInterface
{
    #[Override]
    public function fromArray(array $data): ?object
    {
        return new TestUser($data['username'], $data['password']);
    }

    #[Override]
    public function toArray(object $user): array
    {
        return [
            'username' => $user->username,
            'password' => $user->password,
        ];
    }
}
