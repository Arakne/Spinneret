<?php

namespace Arakne\Spinneret\Security\User;

/**
 * Base type for handle users on the session.
 *
 * @template U as object
 */
interface UserHandlerInterface
{
    /**
     * Parse the session data to a user object.
     *
     * This method should not throw any exception.
     * If any invalid data is found, it should return null.
     *
     * The handler may perform additional checks to ensure the data is valid.
     * It's not required to reload user data from the database, this will be done on refresh, with lower frequency.
     *
     * @param array $data The session data.
     *
     * @return U|null The user object or null if the data is invalid.
     */
    public function fromArray(array $data): ?object;

    /**
     * Transform the user object to a session data array.
     *
     * The result array must be serializable to JSON.
     * This array must be able to be passed to the {@see UserHandlerInterface::fromArray()} method to recreate the user object.
     *
     * @param U $user
     * @return array
     */
    public function toArray(object $user): array;

    // @todo refresh & check role ?
}
