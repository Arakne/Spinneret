<?php

namespace Arakne\Spinneret\Time;

use DateTimeImmutable;
use DateTimeZone;

final readonly class TimeConfiguration
{
    public function __construct(
        public DateTimeZone $timeZone = new DateTimeZone('UTC'),

        /**
         * If set, this time will be used as the current time instead of the system time.
         * Useful for testing purposes.
         *
         * Use null to use the system time.
         *
         * Note: this value is used during compile time to determine the clock implementation to use,
         * so do not define it dynamically at runtime using environment variables or similar methods.
         */
        public ?DateTimeImmutable $fixedTime = null,
    ) {}
}
