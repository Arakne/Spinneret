<?php

namespace Arakne\Tests\Spinneret\Scheduler\Fixtures;

use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\TimeUnit;

use function apcu_store;
use function microtime;

#[ScheduledTask(new ScheduleDelay(15, TimeUnit::Minutes), name: 'invokable-service')]
final readonly class InvokableServiceTask
{
    public function __invoke(): void
    {
    }
}
