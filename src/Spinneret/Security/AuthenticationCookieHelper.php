<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Arakne\Spinneret\Util\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Random\Engine\Secure;
use Random\Randomizer;

use function bin2hex;
use function var_dump;

/**
 * Utility class to help with authentication cookies.
 */
final readonly class AuthenticationCookieHelper
{
    private ClockInterface $clock;
    private Randomizer $random;

    public function __construct(
        private SecurityConfig $config,
        private CookieSerializerInterface $cookieSerializer,
        private UserHandlerInterface $userHandler,
        ?Randomizer $random = null,
        ?ClockInterface $clock = null,
    ) {
        $this->random = $random ?? new Randomizer(new Secure());
        $this->clock = $clock ?? SystemClock::instance();
    }

    /**
     * Create the cookie with the given data.
     *
     * @param object|null $data The data to store in the cookie.
     *
     * @return ParsedCookie
     */
    public function createCookie(?object $data = null): ParsedCookie
    {
        $now = $this->clock->now()->getTimestamp();

        return new ParsedCookie(
            bin2hex($this->random->getBytes(16)),
            $now,
            $now,
            $now + $this->config->ttl,
            $this->config->version,
            $data,
        );
    }

    /**
     * Serialize the cookie and return it as Set-Cookie header value.
     *
     * @param object|ParsedCookie|null $data The data to store in the cookie. Can be a ParsedCookie instance.
     *
     * @return string
     */
    public function getCookieString(?object $data): string
    {
        if (!$data instanceof ParsedCookie) {
            $data = $this->createCookie($data);
        }

        return $this->config->cookie->format($this->cookieSerializer->toString($data));
    }

    /**
     * Check if the cookie should be refreshed.
     *
     * @param ParsedCookie $cookie
     * @return bool True if the cookie should be refreshed.
     */
    public function shouldBeRefreshed(ParsedCookie $cookie): bool
    {
        $delta = $this->clock->now()->getTimestamp() - $cookie->refresh;

        return $delta > $this->config->refreshThreshold;
    }

    /**
     * Refresh the session and update the cookie.
     *
     * @param ParsedCookie $cookie
     * @return ParsedCookie The updated cookie.
     */
    public function refreshCookie(ParsedCookie $cookie): ParsedCookie
    {
        $now = $this->clock->now()->getTimestamp();

        if ($this->config->extendExpiration) {
            $expiration = $now + $this->config->ttl;
        } else {
            $expiration = $cookie->expiration;
        }

        return $cookie->refresh(
            $cookie->data ? $this->userHandler->refresh($cookie->data) : null,
            $now,
            $expiration
        );
    }

    /**
     * Write the Set-Cookie header to the response with the given payload.
     *
     * @param ResponseInterface $response The response to modify.
     * @param object|null $data The data to store in the cookie. Can be a ParsedCookie instance.
     *
     * @return ResponseInterface The modified response.
     */
    public function writeResponse(ResponseInterface $response, ?object $data): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->getCookieString($data));
    }

    /**
     * Modify the response to remove the authentication cookie.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface The modified response.
     */
    public function removeCookie(ResponseInterface $response): ResponseInterface
    {
        return $response->withAddedHeader('Set-Cookie', $this->config->cookie->name . '=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=' . $this->config->cookie->path . ';');
    }
}
