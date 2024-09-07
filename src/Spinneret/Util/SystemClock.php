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

    /**
     * Get the current instance of the system clock.
     */
    public static function instance(): SystemClock
    {
        /** @var SystemClock $instance */
        static $instance = new self();

        return $instance;
    }
}
