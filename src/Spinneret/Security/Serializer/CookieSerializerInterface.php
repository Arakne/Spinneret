<?php

namespace Arakne\Spinneret\Security\Serializer;

interface CookieSerializerInterface
{
    public function fromCookie(string $cookie): ?ParsedCookie;
    public function toCookie(object $user): string;
}
