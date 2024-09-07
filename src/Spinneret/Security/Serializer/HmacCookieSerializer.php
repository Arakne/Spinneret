<?php

namespace Arakne\Spinneret\Security\Serializer;

use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Spinneret\Security\User\UserHandlerInterface;
use Arakne\Spinneret\Util\SystemClock;
use Override;
use Psr\Clock\ClockInterface;

use function array_key_exists;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function gzdeflate;
use function gzinflate;
use function hash_equals;
use function hash_hmac;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

/**
 * Cookie serializer that uses HMAC to sign and verify the cookie.
 */
final readonly class HmacCookieSerializer implements CookieSerializerInterface
{
    private ClockInterface $clock;

    public function __construct(
        private UserHandlerInterface $userHandler,

        /**
         * The secret key used to sign the cookie.
         *
         * Should be kept secret and never shared.
         * It's recommended to use a long random string of 512 characters or more.
         */
        private string $secret,

        /**
         * The current version of the cookie.
         *
         * This value will be compared with the version of the parsed cookie.
         * If versions does not match, the cookie will be considered invalid.
         *
         * @see SecurityConfig::$version
         * @see ParsedCookie::$version
         */
        private int $version = 1,

        /**
         * The hash algorithm used to sign the cookie.
         * The algorithm must be cryptographically secure.
         */
        private string $algorithm = 'sha512',

        /**
         * Whether to compress the cookie payload before signing it.
         */
        private bool $compress = true,

        /**
         * Clock used to get the current time.
         * By default, will use {@see SystemClock}.
         */
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? SystemClock::instance();
    }

    #[Override]
    public function fromString(string $cookie): ?ParsedCookie
    {
        $parts = explode('.', $cookie, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$data, $signature] = $parts;

        $data = @base64_decode($data, true);
        $signature = @base64_decode($signature, true);

        if ($data === false || $signature === false) {
            return null;
        }

        $expectedSignature = hash_hmac($this->algorithm, $data, $this->secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        if ($this->compress) {
            if (($data = @gzinflate($data)) === false) {
                return null;
            }
        }

        /** @var mixed $data */
        $data = @json_decode($data, true);

        if (
            !is_array($data)
            || !isset($data['t'], $data['c'], $data['e'], $data['v'])
            || !array_key_exists('d', $data)
            || !is_string($data['t'])
            || !is_int($data['c'])
            || !is_int($data['e'])
            || !is_int($data['v'])
            || $data['v'] !== $this->version
            || ($data['d'] !== null && !is_array($data['d']))
        ) {
            return null;
        }

        $now = $this->clock->now()->getTimestamp();

        if ($data['c'] > $now || $data['e'] < $now) {
            return null;
        }

        $user = $data['d'] !== null ? $this->userHandler->fromArray($data['d']) : null;

        return new ParsedCookie(
            $data['t'],
            $data['c'],
            $data['e'],
            $data['v'],
            $user,
        );
    }

    #[Override]
    public function toString(ParsedCookie $cookie): string
    {
        $arrPayload = $cookie->data ? $this->userHandler->toArray($cookie->data) : null;

        $data = json_encode([
            't' => $cookie->token,
            'c' => $cookie->creation,
            'e' => $cookie->expiration,
            'v' => $cookie->version,
            'd' => $arrPayload,
        ]);

        if ($this->compress) {
            $data = gzdeflate($data);
        }

        $signature = hash_hmac($this->algorithm, $data, $this->secret, true);

        return base64_encode($data) . '.' . base64_encode($signature);
    }
}
