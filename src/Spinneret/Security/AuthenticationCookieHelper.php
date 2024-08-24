<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class AuthenticationCookieHelper
{
    public function __construct(
        private SecurityConfig $config,
        private CookieSerializerInterface $cookieSerializer,
    ) {
    }

    public function getCookieString(object $account): string
    {
        return $this->config->cookie->format($this->cookieSerializer->toCookie($account));
    }

    public function writeResponse(ResponseInterface $response, object $account): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->getCookieString($account));
    }

    public function removeCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->config->cookie->name.'=; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
