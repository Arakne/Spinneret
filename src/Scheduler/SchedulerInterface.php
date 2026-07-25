<?php

namespace Arakne\Spinneret\Scheduler;

/**
 * A scheduler that can execute tasks at a specified time or at a fixed rate in the future.
 * Tasks may be executed in a background process.
 */
interface SchedulerInterface
{
    /**
     * Add a new task to the scheduler, which will be executed after calling {@see SchedulerInterface::start()} when its delay is reached.
     */
    public function add(ScheduledTaskInterface $task): void;

    /**
     * Start the scheduler.
     * The scheduler will stop when there are no more tasks to execute, or when the given timeout is reached.
     *
     * @param int|null $timeout The maximum execution time of the scheduler in milliseconds. If null, the scheduler will run indefinitely.
     */
    public function start(?int $timeout = null): void;
}
