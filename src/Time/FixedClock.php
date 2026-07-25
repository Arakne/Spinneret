<?php

namespace Arakne\Spinneret\Time;

use DateTimeImmutable;
use DateTimeZone;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Implementation of the ClockInterface that always returns a fixed time.
 * This is useful for testing purposes.
 */
final class FixedClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
        private readonly ?DateTimeZone $timeZone = null,
    ) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        $now = $this->now;

        if ($this->timeZone !== null) {
            $now = $now->setTimezone($this->timeZone);
        }

        return $now;
    }

    /**
     * Change the current time of the clock.
     *
     * @param DateTimeImmutable $now
     * @return void
     */
    public function setNow(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
