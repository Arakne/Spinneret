<?php

namespace Arakne\Spinneret\Scheduler;

use Stringable;

use function hrtime;

/**
 * Represents a delay for a scheduled task execution.
 */
interface ScheduleDelayInterface extends Stringable
{
    /**
     * Get the delay as milliseconds from the given origin timepoint.
     *
     * The returned value is usually `$origin + delay in ms`.
     * An absolute value can also be used if you want to schedule a task at a fixed date.
     * If the returned value is lower (or equal) than $origin, the task will be executed as soon as possible.
     *
     * @param int $origin The timepoint when the delay is started. This value represents milliseconds, from an arbitrary time, usually returned by {@see hrtime()}.
     * @return int The timepoint when the delay is reached
     */
    public function toMilliseconds(int $origin): int;
}
