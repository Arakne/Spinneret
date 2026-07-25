<?php

namespace Arakne\Spinneret\Scheduler\Locator;

use Arakne\Spinneret\Scheduler\ScheduledTaskInterface;

/**
 * @internal
 */
final readonly class ScheduledTaskRegistry
{
    public function __construct(
        /**
         * @var array<ScheduledTaskInterface>
         */
        private array $tasks,
    ) {}

    /**
     * Get all the registered scheduled tasks.
     *
     * @return array<ScheduledTaskInterface>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }
}
