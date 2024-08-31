<?php

namespace Arakne\Spinneret\Security\Serializer;

use Psr\Clock\ClockInterface;
use Random\Randomizer;

final readonly class ParsedCookie
{
    public function __construct(
        public string $token,
        public int $creation,
        public int $expiration,
        public int $version,
        public ?object $data,
    ) {
    }
}
