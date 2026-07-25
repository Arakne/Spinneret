<?php

namespace Arakne\Spinneret;

/**
 * Store the current version of Spinneret.
 */
final readonly class SpinneretVersion
{
    public const int MAJOR = 0;
    public const int MINOR = 1;
    public const int PATCH = 0;
    public const string EXTRA = '-alpha';

    public const string FULL = self::MAJOR . '.' . self::MINOR . '.' . self::PATCH . self::EXTRA;
}
