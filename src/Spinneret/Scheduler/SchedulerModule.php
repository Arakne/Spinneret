<?php

namespace Arakne\Spinneret\Scheduler;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\TaggedServiceIterator;
use Arakne\Spinneret\Scheduler\Console\StartSchedulerCommand;
use Arakne\Spinneret\Scheduler\Locator\RegisterScheduledTaskProcessor;
use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\Locator\ScheduledTaskRegistry;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Module that enables the scheduler worker command.
 *
 * This module is not required if you only want to use the scheduler manually.
 */
final readonly class SchedulerModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->processor(new RegisterScheduledTaskProcessor());
        $containerBuilder->register(ScheduledTaskRegistry::class, [
            new TaggedServiceIterator(ScheduledTask::class, true),
        ]);
        $containerBuilder->register(StartSchedulerCommand::class, [
            new Reference(ScheduledTaskRegistry::class),
            new Reference(LoggerInterface::class, nullOnInvalid: true),
        ]);
    }
}
