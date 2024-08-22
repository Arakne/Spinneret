<?php

namespace Arakne\Spinneret\Security;

use Override;

/**
 * @implements UserHandlerInterface<object>
 */
final class ObjectUserHandler implements UserHandlerInterface
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
