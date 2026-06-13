<?php

namespace Arakne\Tests\Spinneret\Scheduler\Fixtures;

use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\ScheduleDelayInterface;
use Arakne\Spinneret\Scheduler\ScheduledTaskInterface;
use Override;

use function apcu_store;
use function microtime;

#[ScheduledTask]
final readonly class SimpleTask implements ScheduledTaskInterface
{
    #[Override]
    public function name(): string
    {
        return 'simple-task';
    }

    #[Override]
    public function run(): bool
    {
        apcu_store(self::class, microtime(true));

        return true;
    }

    #[Override]
    public function perpetual(): bool
    {
        return true;
    }

    #[Override]
    public function delay(): ScheduleDelayInterface
    {
        return ScheduleDelay::milliseconds(100);
    }
}
