<?php

namespace Arakne\Tests\Spinneret\Scheduler;

use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Scheduler\ClosureScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\ScheduleDelayInterface;
use Arakne\Spinneret\Scheduler\Scheduler;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_fetch;
use function apcu_inc;
use function apcu_store;
use function array_column;
use function microtime;
use function strtoupper;
use function strtr;
use function usleep;
use function var_export;

class SchedulerTest extends TestCase
{
    private const string TEST_VALUE_KEY = 'scheduler_test_value';
    private const string TEST_WRITE_TIME = 'scheduler_time';

    private ArrayLogger $logger;
    private Scheduler $scheduler;

    protected function setUp(): void
    {
        $this->logger = new ArrayLogger();
        $this->scheduler = new Scheduler($this->logger);
    }

    protected function tearDown(): void
    {
        apcu_clear_cache();
    }

    #[Test]
    public function startWithoutTaskShouldStopImmediately()
    {
        $start = microtime(true);
        $this->scheduler->start();
        $this->assertCount(0, $this->logger->logs);
        $this->assertLessThan(0.01, microtime(true) - $start);
    }

    #[Test]
    public function startSingleTask()
    {
        $task = new ClosureScheduledTask(
            function () {
                $this->write(42);
            },
            ScheduleDelay::milliseconds(100),
            name: 'Write 42',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start();
        $this->assertLogs([
            'INFO Running task Write 42',
            'INFO Task Write 42 is running in background with PID {pid}',
            'INFO Task Write 42 with PID {pid} has terminated with status 0',
        ]);
        $this->assertExecutedAt($start + 0.1);
        $this->assertValue(42);
    }

    #[Test]
    public function taskWithException()
    {
        $task = new ClosureScheduledTask(
            function () {
                $this->write(0);
                throw new Exception('Test exception');
            },
            ScheduleDelay::milliseconds(100),
            name: 'Error',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start();
        $this->assertLogs([
            'INFO Running task Error',
            'INFO Task Error is running in background with PID {pid}',
            'INFO Task Error with PID {pid} has terminated with status 1',
        ]);
        $this->assertExecutedAt($start + 0.1);
    }

    #[Test]
    public function taskWithReturnFalse()
    {
        $task = new ClosureScheduledTask(
            function () {
                $this->write(0);
                return false;
            },
            ScheduleDelay::milliseconds(100),
            name: 'Error',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start();
        $this->assertLogs([
            'INFO Running task Error',
            'INFO Task Error is running in background with PID {pid}',
            'INFO Task Error with PID {pid} has terminated with status 1',
        ]);
        $this->assertExecutedAt($start + 0.1);
    }

    #[Test]
    public function perpetualTask()
    {
        apcu_store('counter', 0);
        apcu_store('times', []);

        $task = new ClosureScheduledTask(
            function () {
                apcu_inc('counter');
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);
            },
            ScheduleDelay::milliseconds(50),
            perpetual: true,
            name: 'Perpetual',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start(200);

        $this->assertGreaterThan(1, apcu_fetch('counter'));
        $expectedTimes = [];

        for ($c = 0; $c < apcu_fetch('counter'); $c++) {
            $expectedTimes[] = $start + 0.05 * ($c + 1);
        }

        $this->assertEqualsWithDelta($expectedTimes, apcu_fetch('times'), 0.01);
    }

    #[Test]
    public function twoTaskDifferentTime()
    {
        apcu_store('values', []);
        apcu_store('times', []);

        $task1 = new ClosureScheduledTask(
            function () {
                $values = apcu_fetch('values');
                $values[] = 'task1';
                apcu_store('values', $values);
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);
            },
            ScheduleDelay::milliseconds(100),
            name: 'Task 1',
        );

        $task2 = new ClosureScheduledTask(
            function () {
                $values = apcu_fetch('values');
                $values[] = 'task2';
                apcu_store('values', $values);
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);
            },
            ScheduleDelay::milliseconds(50),
            name: 'Task 2',
        );

        $start = microtime(true);
        $this->scheduler->add($task1);
        $this->scheduler->add($task2);
        $this->scheduler->start();

        $this->assertLogs([
            'INFO Running task Task 2',
            'INFO Task Task 2 is running in background with PID {pid}',
            'INFO Task Task 2 with PID {pid} has terminated with status 0',
            'INFO Running task Task 1',
            'INFO Task Task 1 is running in background with PID {pid}',
            'INFO Task Task 1 with PID {pid} has terminated with status 0',
        ]);
        $this->assertEquals(['task2', 'task1'], apcu_fetch('values'));
        $this->assertEqualsWithDelta([$start + 0.05, $start + 0.1], apcu_fetch('times'), 0.01);
    }

    #[Test]
    public function twoTaskSameTime()
    {
        apcu_store('count', 0);
        apcu_store('times', []);

        $task1 = new ClosureScheduledTask(
            function () {
                apcu_inc('count');
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);
            },
            ScheduleDelay::milliseconds(50),
            name: 'Task 1',
        );

        $task2 = new ClosureScheduledTask(
            function () {
                apcu_inc('count');
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);
            },
            ScheduleDelay::milliseconds(50),
            name: 'Task 2',
        );

        $start = microtime(true);
        $this->scheduler->add($task1);
        $this->scheduler->add($task2);
        $this->scheduler->start();

        $this->assertLogs([
            'INFO Running task Task 1',
            'INFO Task Task 1 is running in background with PID {pid}',
            'INFO Running task Task 2',
            'INFO Task Task 2 is running in background with PID {pid}',
            'INFO Task Task 1 with PID {pid} has terminated with status 0',
            'INFO Task Task 2 with PID {pid} has terminated with status 0',
        ]);
        $this->assertSame(2, apcu_fetch('count'));
        $this->assertEqualsWithDelta([$start + 0.05, $start + 0.05], apcu_fetch('times'), 0.01);
    }

    #[Test]
    public function longPerpetualTaskSingleInstance()
    {
        apcu_store('count', 0);
        apcu_store('times', []);

        $task = new ClosureScheduledTask(
            function () {
                apcu_inc('count');
                $times = apcu_fetch('times');
                $times[] = microtime(true);
                apcu_store('times', $times);

                usleep(200000); // Sleep for 200ms to simulate a long task
            },
            ScheduleDelay::milliseconds(100),
            perpetual: true,
            name: 'Long',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start(400);

        $this->assertSame(2, apcu_fetch('count'));
        $this->assertEqualsWithDelta([$start + 0.1, $start + 0.3], apcu_fetch('times'), 0.05);
        $this->assertContains('Task {task} is still running, deferring execution', array_column($this->logger->logs, 'message'));
    }

    #[Test]
    public function timeoutShouldStopBeforeFutureTaskExecution()
    {
        $task = new ClosureScheduledTask(
            function () {
                $this->write(99);
            },
            ScheduleDelay::milliseconds(100),
            name: 'Future write',
        );

        $this->scheduler->add($task);
        $this->scheduler->start(20);

        $this->assertFalse(apcu_fetch(self::TEST_VALUE_KEY));
        $this->assertCount(0, $this->logger->logs);
    }

    #[Test]
    public function taskWithPastDelayShouldRunImmediately()
    {
        $task = new ClosureScheduledTask(
            function () {
                $this->write(7);
            },
            new class implements ScheduleDelayInterface {
                public function toMilliseconds(int $origin): int
                {
                    return $origin - 1;
                }

                public function __toString(): string
                {
                    return '';
                }
            },
            name: 'Immediate',
        );

        $start = microtime(true);
        $this->scheduler->add($task);
        $this->scheduler->start();

        $this->assertLogs([
            'INFO Running task Immediate',
            'INFO Task Immediate is running in background with PID {pid}',
            'INFO Task Immediate with PID {pid} has terminated with status 0',
        ]);
        $this->assertExecutedAt($start);
        $this->assertValue(7);
    }

    private function write(mixed $value): void
    {
        apcu_store(self::TEST_VALUE_KEY, $value);
        apcu_store(self::TEST_WRITE_TIME, microtime(true));
    }

    private function read(): mixed
    {
        return apcu_fetch(self::TEST_VALUE_KEY);
    }

    private function assertLogs(array $expected): void
    {
        $actual = [];

        foreach ($this->logger->logs as $log) {
            $actual[] = strtoupper($log['level']) . ' ' . strtr($log['message'], [
                '{task}' => $log['context']['task'] ?? '',
                '{status}' => $log['context']['status'] ?? '',
                '{exception}' => isset($log['context']['exception']) ? $log['context']['exception']->getMessage() : '',
            ]);
        }

        $this->assertEquals($expected, $actual);
    }

    private function assertExecutedAt(float $expected): void
    {
        $this->assertEqualsWithDelta($expected, apcu_fetch(self::TEST_WRITE_TIME), 0.01);
    }

    private function assertValue(mixed $expected): void
    {
        $this->assertSame($expected, $this->read());
    }
}
