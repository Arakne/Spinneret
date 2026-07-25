<?php

namespace Arakne\Spinneret\Security\Serializer;

/**
 * Serialize and deserialize authentication cookies.
 * The implementation is responsible for the security of the cookie data.
 */
interface CookieSerializerInterface
{
    /**
     * Parse a cookie value and return the parsed data.
     *
     * If the value is invalid or the data is corrupted, it should return null.
     * This method should not throw any exception.
     */
    public function fromString(string $cookie): ?ParsedCookie;

    /**
     * Serialize and sign the cookie data.
     *
     * @param ParsedCookie $cookie The cookie data to serialize.
     */
    public function toString(ParsedCookie $cookie): string;
}
