<?php

namespace Arakne\Spinneret\Scheduler;

/**
 * Represents a unit of time for scheduling tasks.
 */
enum TimeUnit
{
    case Millisecond;
    case Second;
    case Minutes;
    case Hours;
    case Days;

    /**
     * Convert the value in the current unit to milliseconds.
     *
     * @param non-negative-int $value The time value in the current unit. Must be a non-negative integer.
     * @return non-negative-int
     */
    public function toMilliseconds(int $value): int
    {
        return match ($this) {
            self::Millisecond => $value,
            self::Second => $value * 1000,
            self::Minutes => $value * 60 * 1000,
            self::Hours => $value * 60 * 60 * 1000,
            self::Days => $value * 24 * 60 * 60 * 1000,
        };
    }

    /**
     * Get a human-readable string representation of the time value in the current unit.
      *
      * @param non-negative-int $value The time value in the current unit. Must be a non-negative integer.
      * @return string
     */
    public function format(int $value): string
    {
        return match ($this) {
            self::Millisecond => "$value ms",
            self::Second => "$value s",
            self::Minutes => "$value min",
            self::Hours => "$value h",
            self::Days => "$value d",
        };
    }
}
