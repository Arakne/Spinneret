<?php

namespace Arakne\Tests\Spinneret\Scheduler\Locator;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Scheduler\Locator\ScheduledTask;
use Arakne\Spinneret\Scheduler\Locator\ScheduledTaskRegistry;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\ScheduledTaskInterface;
use Arakne\Spinneret\Scheduler\SchedulerModule;
use Arakne\Spinneret\Scheduler\TimeUnit;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AutoDiscoverTest extends TestCase
{
    #[Test]
    public function tasks()
    {
        $container = new ContainerBuilder(registerAsPublic: true);

        new SchedulerModule()->register($container);
        $container->import(__DIR__ . '/../Fixtures/', 'Arakne\Tests\Spinneret\Scheduler\Fixtures');

        $built = $container->build();
        $tasks = $built->get(ScheduledTaskRegistry::class)->tasks();

        $this->assertCount(4, $tasks);
        $this->assertContainsOnlyInstancesOf(ScheduledTaskInterface::class, $tasks);

        $tasksByName = [];
        foreach ($tasks as $task) {
            $tasksByName[$task->name()] = $task;
        }

        $this->assertArrayHasKey('simple-task', $tasksByName);
        $this->assertArrayHasKey('foo', $tasksByName);
        $this->assertArrayHasKey('bar', $tasksByName);
        $this->assertArrayHasKey('invokable-service', $tasksByName);

        $this->assertEquals(new ScheduleDelay(100, TimeUnit::Millisecond), $tasksByName['simple-task']->delay());
        $this->assertEquals(new ScheduleDelay(1, TimeUnit::Hours), $tasksByName['foo']->delay());
        $this->assertEquals(new ScheduleDelay(1, TimeUnit::Hours), $tasksByName['bar']->delay());
        $this->assertEquals(new ScheduleDelay(15, TimeUnit::Minutes), $tasksByName['invokable-service']->delay());

        $this->assertTrue($tasksByName['simple-task']->perpetual());
        $this->assertTrue($tasksByName['foo']->perpetual());
        $this->assertFalse($tasksByName['bar']->perpetual());
        $this->assertTrue($tasksByName['invokable-service']->perpetual());

        $this->assertTrue($tasksByName['simple-task']->run());
        $this->assertTrue($tasksByName['foo']->run());
        $this->assertTrue($tasksByName['bar']->run());
        $this->assertTrue($tasksByName['invokable-service']->run());
    }

    #[Test]
    public function notInvokableServiceError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Arakne\Tests\Spinneret\Scheduler\Locator\NotInvokable must have a public __invoke method without arguments to be used as a scheduled task.');

        $container = new ContainerBuilder(registerAsPublic: true);

        new SchedulerModule()->register($container);
        $container->register(NotInvokable::class);

        $container->build();
    }

    #[Test]
    public function invokableWithRequiredArgsError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Arakne\Tests\Spinneret\Scheduler\Locator\WithRequiredArgs must have a public __invoke method without arguments to be used as a scheduled task.');

        $container = new ContainerBuilder(registerAsPublic: true);

        new SchedulerModule()->register($container);
        $container->register(WithRequiredArgs::class);

        $container->build();
    }

    #[Test]
    public function invokableMissingDelayError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The delay parameter must be provided for service Arakne\Tests\Spinneret\Scheduler\Locator\MissingDelay when using the Arakne\Spinneret\Scheduler\Locator\ScheduledTask attribute on a method.');

        $container = new ContainerBuilder(registerAsPublic: true);

        new SchedulerModule()->register($container);
        $container->register(MissingDelay::class);

        $container->build();
    }

    #[Test]
    public function invalidTaskMethod()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method Arakne\Tests\Spinneret\Scheduler\Locator\InvalidTaskMethod::requiredParameter cannot be registered as a scheduled task because it has required parameters.');

        $container = new ContainerBuilder(registerAsPublic: true);

        new SchedulerModule()->register($container);
        $container->register(InvalidTaskMethod::class);

        $container->build();
    }
}

#[ScheduledTask(new ScheduleDelay(1, TimeUnit::Hours))]
class NotInvokable
{
}

#[ScheduledTask(new ScheduleDelay(1, TimeUnit::Hours))]
class WithRequiredArgs
{
    public function __invoke(string $arg): void
    {
    }
}

#[ScheduledTask]
class MissingDelay
{
    public function __invoke(): void
    {
    }
}

class InvalidTaskMethod
{
    #[ScheduledTask(new ScheduleDelay(1, TimeUnit::Hours))]
    public function requiredParameter(string $v): void
    {

    }
}
