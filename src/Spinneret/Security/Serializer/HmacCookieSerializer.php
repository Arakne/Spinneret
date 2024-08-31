<?php

namespace Arakne\Spinneret\Security\Serializer;

use Arakne\Spinneret\Security\UserHandlerInterface;
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

final readonly class HmacCookieSerializer implements CookieSerializerInterface
{
    private ClockInterface $clock;

    public function __construct(
        private UserHandlerInterface $userHandler,
        private string $secret,
        private int $version = 1,
        private string $algorithm = 'sha512',
        private bool $compress = true,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? SystemClock::instance();
    }

    #[Override]
    public function fromString(string $cookie): ?ParsedCookie
    {
        $parts = explode('.', $cookie);

        if (count($parts) !== 2) {
            return null;
        }

        [$data, $signature] = $parts;

        $data = @base64_decode($data);
        $signature = @base64_decode($signature);

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

        $user = $data['d'] ? $this->userHandler->fromArray($data['d']) : null;

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
