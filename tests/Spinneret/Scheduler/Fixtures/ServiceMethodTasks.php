<?php

namespace Arakne\Tests\Spinneret\Scheduler\Fixtures;

use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\TimeUnit;

final readonly class ServiceMethodTasks
{
    #[ScheduledTask(new ScheduleDelay(1, TimeUnit::Hours), name: 'foo')]
    public function foo(): void
    {

    }

    #[ScheduledTask(new ScheduleDelay(1, TimeUnit::Hours), perpetual: false, name: 'bar')]
    public function bar(): void
    {

    }
}
