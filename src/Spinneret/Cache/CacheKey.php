<?php

namespace Arakne\Spinneret\Cache;

use Arakne\Spinneret\Cache\Exception\InvalidKeyException;

use function implode;
use function preg_match;
use function preg_quote;
use function str_repeat;
use function strlen;
use function strtr;

final readonly class CacheKey
{
    public const string DISALLOWED_CHARACTERS = '{}()/\@:';

    /**
     * Check if the given cache key is valid and does not contain any disallowed characters or throw {@see InvalidKeyException} if it is not valid.
     *
     * @param string $key
     * @throws InvalidKeyException if the key contains disallowed characters
     */
    public static function assertValidKey(string $key): void
    {
        /** @var non-empty-string|null $regex */
        static $regex = null;

        $regex ??= '/[' . preg_quote(self::DISALLOWED_CHARACTERS, '/') . ']/';

        if (preg_match($regex, $key)) {
            throw new InvalidKeyException($key);
        }
    }

    /**
     * Convert a string or an array of strings into a valid cache key by concatenating the array elements with dots
     * and removing any disallowed characters. The resulting key will be a string that can be used as a cache key.
     *
     * @param string|list<string> $key
     *
     * @return string
     */
    public static function toCacheKey(string|array $key): string
    {
        if (is_array($key)) {
            $key = implode('.', $key);
        }

        return strtr($key, self::DISALLOWED_CHARACTERS, str_repeat('_', strlen(self::DISALLOWED_CHARACTERS)));
    }
}
