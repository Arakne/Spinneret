<?php

namespace Arakne\Spinneret\Scheduler;

/**
 * Represents a task that can be scheduled to run at a specific time or after a certain delay.
 *
 * @see SchedulerInterface::add() to add a task to the scheduler.
 */
interface ScheduledTaskInterface
{
    /**
     * Get the task name
     */
    public function name(): string;

    /**
     * Run the task
     *
     * @return bool True if the task has run successfully, false otherwise
     */
    public function run(): bool;

    /**
     * Whether the task should be run perpetually (i.e., every time the scheduler runs) or only once.
     *
     * @return bool true if the task should be run every time, or false to run only once
     */
    public function perpetual(): bool;

    /**
     * Get the delay before the task should be run.
     */
    public function delay(): ScheduleDelayInterface;
}
