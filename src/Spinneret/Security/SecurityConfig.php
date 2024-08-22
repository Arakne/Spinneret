<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;

final readonly class SecurityConfig
{
    public function __construct(
        public bool $enabled = true,
        public string $cookieName = 'auth',
        public ?string $secret = null,
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
