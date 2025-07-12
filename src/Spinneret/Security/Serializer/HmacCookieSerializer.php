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
use function rtrim;
use function strtr;

/**
 * Cookie serializer that uses HMAC to sign and verify the cookie.
 */
final readonly class HmacCookieSerializer implements CookieSerializerInterface
{
    /**
     * Maximum length of the uncompressed data.
     */
    private const int MAX_DATA_LENGTH = 1_000_000;

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

        $data = self::base64UrlDecode($data);
        $signature = self::base64UrlDecode($signature);

        if ($data === false || $signature === false) {
            return null;
        }

        $expectedSignature = hash_hmac($this->algorithm, $data, $this->secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        if ($this->compress) {
            if (($data = @gzinflate($data, self::MAX_DATA_LENGTH)) === false) {
                return null;
            }
        }

        /** @var mixed $data */
        $data = @json_decode($data, true);

        if (
            !is_array($data)
            || count($data) !== 5
            || !isset($data['t'], $data['c'], $data['e'], $data['v'])
            || !array_key_exists('d', $data)
            || !is_string($data['t'])
            || !is_int($data['c'])
            || !is_int($data['e'])
            || !is_int($data['v'])
            || $data['v'] !== $this->version
            || ($data['d'] !== null && !is_array($data['d']))
        ) {
            // @todo log invalid cookie format - this may indicate a secret leak
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

    /**
     * @psalm-suppress PossiblyFalseArgument
     */
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

        /** @var string $signature Cannot be null since PHP 8.0 */
        $signature = hash_hmac($this->algorithm, $data, $this->secret, true);

        return self::base64UrlEncode($data) . '.' . self::base64UrlEncode($signature);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        return @base64_decode(strtr($value, '-_', '+/'), true);
    }
}
