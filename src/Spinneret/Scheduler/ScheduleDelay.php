<?php

namespace Arakne\Spinneret\Scheduler;

use Override;

/**
 * Simple delay implementation.
 */
final readonly class ScheduleDelay implements ScheduleDelayInterface
{
    public function __construct(
        /**
         * @var non-negative-int
         */
        public int $value,
        public TimeUnit $unit,
    ) {}

    #[Override]
    public function toMilliseconds(int $origin): int
    {
        return $origin + $this->unit->toMilliseconds($this->value);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->unit->format($this->value);
    }

    /**
     * Create a delay of the given number of milliseconds.
     *
     * @param non-negative-int $value
     */
    public static function milliseconds(int $value): self
    {
        return new self($value, TimeUnit::Millisecond);
    }

    /**
     * Create a delay of the given number of seconds.
     *
     * @param non-negative-int $value
     */
    public static function seconds(int $value): self
    {
        return new self($value, TimeUnit::Second);
    }

    /**
     * Create a delay of the given number of minutes.
     *
     * @param non-negative-int $value
     */
    public static function minutes(int $value): self
    {
        return new self($value, TimeUnit::Minutes);
    }
}
