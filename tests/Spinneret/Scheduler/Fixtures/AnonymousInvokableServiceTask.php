<?php

namespace Arakne\Tests\Spinneret\Scheduler\Fixtures;

use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\TimeUnit;

#[ScheduledTask(new ScheduleDelay(20, TimeUnit::Minutes))]
final readonly class AnonymousInvokableServiceTask
{
    public function __invoke(): void
    {
    }
}
