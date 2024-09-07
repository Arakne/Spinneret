<?php

namespace Arakne\Spinneret\Security\User;

use Override;

/**
 * Simple user handler that handles user as stdClass.
 *
 * @implements UserHandlerInterface<object>
 */
final readonly class ObjectUserHandler implements UserHandlerInterface
{
    #[Override]
    public function fromArray(array $data): ?object
    {
        return (object) $data;
    }

    #[Override]
    public function toArray(object $user): array
    {
        return (array) $user;
    }
}
