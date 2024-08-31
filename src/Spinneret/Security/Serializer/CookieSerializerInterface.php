<?php

namespace Arakne\Spinneret\Security\Serializer;

interface CookieSerializerInterface
{
    public function fromString(string $cookie): ?ParsedCookie;
    public function toString(ParsedCookie $cookie): string;
}
