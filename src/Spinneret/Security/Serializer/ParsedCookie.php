<?php

namespace Arakne\Spinneret\Security\Serializer;

final readonly class ParsedCookie
{
    public function __construct(
        public string $token,
        public int $creation,
        public int $expiration,
        public int $version,
        public object $data,
    ) {
    }
}
