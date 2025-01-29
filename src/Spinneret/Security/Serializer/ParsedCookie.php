<?php

namespace Arakne\Spinneret\Security\Serializer;

/**
 * Store authentication cookie data.
 */
final readonly class ParsedCookie
{
    public function __construct(
        /**
         * Random token.
         * Can be used as base for generate CSRF tokens.
         */
        public string $token,

        /**
         * The creation timestamp of the authentication session.
         * It may differ from the cookie creation timestamp.
         */
        public int $creation,

        /**
         * The expiration timestamp of the authentication session.
         * When reached, the user will be logged out.
         *
         * Note: the session may live longer than this value if the session is refreshed.
         */
        public int $expiration,

        /**
         * The version of the authentication cookie.
         * This value is changed when the cookie format or the user serialization format changes.
         *
         * @see SecurityConfig::$version
         */
        public int $version,

        /**
         * The session payload.
         * Can be null in case of anonymous user.
         */
        public ?object $data,
    ) {}
}
