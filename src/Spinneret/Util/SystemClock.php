<?php

namespace Arakne\Spinneret\Util;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Basic implementation of the ClockInterface using the system clock.
 */
final readonly class SystemClock implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    public static function instance(): SystemClock
    {
        static $instance = new self();

        return $instance;
    }
}
