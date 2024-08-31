<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;

final readonly class SecurityConfig
{
    public function __construct(
        public bool $enabled = true,
        public CookieOptions $cookie = new CookieOptions(),
        public ?string $secret = null,
        public int $version = 1,
        public int $ttl = 3600,
        /**
         * @var class-string<UserHandlerInterface>
         */
        public string $userHandler = ObjectUserHandler::class,

        /**
         * @var class-string<CookieSerializerInterface>
         */
        public string $serializer = HmacCookieSerializer::class,
    ) {
    }
}
