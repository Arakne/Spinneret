<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\Util\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Random\Engine\Secure;
use Random\Randomizer;

use function bin2hex;

final readonly class AuthenticationCookieHelper
{
    private ClockInterface $clock;
    private Randomizer $random;

    public function __construct(
        private SecurityConfig $config,
        private CookieSerializerInterface $cookieSerializer,
        ?Randomizer $random = null,
        ?ClockInterface $clock = null,
    ) {
        $this->random = $random ?? new Randomizer(new Secure());
        $this->clock = $clock ?? SystemClock::instance();
    }

    public function createCookie(?object $data = null): ParsedCookie
    {
        $now = $this->clock->now()->getTimestamp();

        return new ParsedCookie(
            bin2hex($this->random->getBytes(16)),
            $now,
            $now + $this->config->ttl,
            $this->config->version,
            $data,
        );
    }

    public function getCookieString(?object $data): string
    {
        if (!$data instanceof ParsedCookie) {
            $data = $this->createCookie($data);
        }

        return $this->config->cookie->format($this->cookieSerializer->toString($data));
    }

    public function writeResponse(ResponseInterface $response, ?object $account): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->getCookieString($account));
    }

    public function removeCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->config->cookie->name.'=; Expires=Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
