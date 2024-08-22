<?php

namespace Arakne\Spinneret\Security\Serializer;

use Arakne\Spinneret\Security\UserHandlerInterface;
use Arakne\Spinneret\Util\SystemClock;
use Closure;
use Override;

use Psr\Clock\ClockInterface;

use Random\Engine\Secure;
use Random\Randomizer;

use function base64_decode;
use function base64_encode;
use function bin2hex;
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
    private Randomizer $random;

    public function __construct(
        private UserHandlerInterface $userHandler,
        private string $secret,
        private int $version = 1,
        private string $algorithm = 'sha512',
        private bool $compress = true,
        private int $ttl = 3600,
        ?Randomizer $random = null,
        ?ClockInterface $clock = null,
    ) {
        $this->random = $random ?? new Randomizer(new Secure());
        $this->clock = $clock ?? SystemClock::instance();
    }

    #[Override]
    public function fromCookie(string $cookie): ?ParsedCookie
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
            || !isset($data['t'], $data['c'], $data['e'], $data['v'], $data['d'])
            || !is_string($data['t'])
            || !is_int($data['c'])
            || !is_int($data['e'])
            || !is_int($data['v'])
            || $data['v'] !== $this->version
            || !is_array($data['d'])
        ) {
            return null;
        }

        $now = $this->clock->now()->getTimestamp();

        if ($data['c'] > $now || $data['e'] < $now) {
            return null;
        }

        $user = $this->userHandler->fromArray($data['d']);

        if ($user === null) {
            return null;
        }

        return new ParsedCookie(
            $data['t'],
            $data['c'],
            $data['e'],
            $data['v'],
            $user,
        );
    }

    #[Override]
    public function toCookie(object $user): string
    {
        $arrUser = $this->userHandler->toArray($user);
        $now = $this->clock->now()->getTimestamp();
        $token = bin2hex($this->random->getBytes(16));
        $expiration = $now + $this->ttl;

        $data = json_encode([
            't' => $token,
            'c' => $now,
            'e' => $expiration,
            'v' => $this->version,
            'd' => $arrUser,
        ]);

        if ($this->compress) {
            $data = gzdeflate($data);
        }

        $signature = hash_hmac($this->algorithm, $data, $this->secret, true);

        return base64_encode($data) . '.' . base64_encode($signature);
    }
}
