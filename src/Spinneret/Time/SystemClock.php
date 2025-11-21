<?php

namespace Arakne\Spinneret\Time;

use DateTimeImmutable;
use DateTimeZone;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Basic implementation of the ClockInterface using the system clock.
 */
final readonly class SystemClock implements ClockInterface
{
    public function __construct(
        private ?DateTimeZone $timeZone = null,
    ) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(timezone: $this->timeZone);
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
