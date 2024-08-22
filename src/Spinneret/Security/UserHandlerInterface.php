<?php

namespace Arakne\Spinneret\Security;

/**
 * @template U as object
 */
interface UserHandlerInterface
{
    /**
     * @param array $data
     * @return U|null
     */
    public function fromArray(array $data): ?object;

    /**
     * @param U $user
     * @return array
     */
    public function toArray(object $user): array;

    // @todo refresh & check role ?
}
