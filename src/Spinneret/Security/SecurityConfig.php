<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use SensitiveParameter;

/**
 * Configuration for the security module.
 */
final readonly class SecurityConfig
{
    public function __construct(
        /**
         * Enable or disable the security module.
         *
         * Note: This value is not resolved dynamically, so the cache must be cleared after changing it.
         *       Consequently, it cannot be defined using an environment variable.
         */
        public bool $enabled = true,

        /**
         * Configure authentication cookies options.
         */
        public CookieOptions $cookie = new CookieOptions(),

        /**
         * The secret key used to sign the authentication cookie.
         *
         * The key should be a random string of at least 32 characters (512 characters recommended).
         * This key should be kept secret and should not be shared.
         * It's recommended to use a generated key stored in a file.
         *
         * @todo utility to generate a key
         *
         * @var string|null
         */
        #[SensitiveParameter]
        public ?string $secret = null,

        /**
         * Version of the authentication cookie.
         *
         * Can be any integer, used to invalidate old cookies.
         * Increment this value when changing the cookie format, or the user serialization format.
         */
        public int $version = 1,

        /**
         * Lifetime of the session in seconds.
         *
         * Once the idle time is reached, the user will be logged out.
         * The session may be refreshed, and a new cookie will be issued.
         *
         * Note: this is different from the cookie expiration time, which is configured using the {@see CookieOptions::$maxAge} property,
         *       set on {@see SecurityConfig::$cookie}.
         *
         * Any null or negative value will invalidate the session immediately.
         */
        public int $ttl = 3600,

        /**
         * Define the user resolver and serializer for create or parse the session cookie.
         *
         * The user handler must implement the {@see UserHandlerInterface} interface, and must be a service registered in the container.
         * By default, the {@see ObjectUserHandler} is used, which simply stores the user as simple stdClass object.
         *
         * @var class-string<UserHandlerInterface>
         */
        public string $userHandler = ObjectUserHandler::class,

        /**
         * Define the cookie serialization method.
         *
         * The serializer is responsible for storing the user, and applying signature and encryption,
         * and verifying the integrity of the cookie.
         *
         * By default, the {@see HmacCookieSerializer} is used, which signs the cookie using HMAC with SHA-512.
         *
         * @var class-string<CookieSerializerInterface>
         */
        public string $serializer = HmacCookieSerializer::class,

        /**
         * The name of the request attribute where the user data will be stored.
         */
        public string $userAttribute = 'user',
    ) {}

    // @todo debug info pour cacher le secret
}
