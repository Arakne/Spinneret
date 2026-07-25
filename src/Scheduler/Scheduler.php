<?php

namespace Arakne\Spinneret\Scheduler;

use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function assert;
use function hrtime;
use function is_int;
use function max;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function spl_object_id;
use function usleep;

/**
 * Default implementation of the scheduler using fork to run background tasks and hrtime to schedule tasks with millisecond precision.
 * If a task is already running when it's time to execute it again, the scheduler will defer the execution after the previous execution has finished.
 */
final class Scheduler implements SchedulerInterface
{
    private const int MAX_WAIT_TIME = 60_000;

    /**
     * Map of task ID to task instance.
     *
     * @var array<int, ScheduledTaskInterface>
     */
    private array $tasks = [];

    /**
     * Map of task ID to next execution time in milliseconds.
     *
     * @var array<int, int>
     */
    private array $taskTime = [];

    /**
     * Map of task ID to child PID for currently running tasks.
     *
     * @var array<int, int>
     */
    private array $runningTasks = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function add(ScheduledTaskInterface $task): void
    {
        $id = spl_object_id($task);

        $this->tasks[$id] = $task;
        $this->taskTime[$id] = $task->delay()->toMilliseconds($this->now());
    }

    #[Override]
    public function start(?int $timeout = null): void
    {
        $endTime = $timeout !== null ? $this->now() + $timeout : null;

        while ($endTime === null || $this->now() < $endTime) {
            $wait = $this->execute();

            if (!$this->checkTerminated()) {
                $wait = 10_000;
            } elseif ($wait > self::MAX_WAIT_TIME) {
                $wait = self::MAX_WAIT_TIME * 1000;
            } else {
                // usleep only guaranteed to stop for at least the given amount of time,
                // but it has no upper bounds (i.e. the sleep time can be higher than the requested one).
                // So, to mitigate that and increase the accuracy of the scheduling, we sleep for slightly less than the requested time.
                $wait *= 900;
            }

            // No more task to execute: stop the scheduler.
            if ($this->taskTime === [] && $this->runningTasks === []) {
                break;
            }

            usleep($wait);
        }
    }

    /**
     * Execute all tasks that are due to be run, and return the delay until the next task should be executed.
     *
     * @return positive-int The delay in milliseconds.
     */
    private function execute(): int
    {
        $now = $this->now();
        $next = PHP_INT_MAX;

        foreach ($this->taskTime as $id => $time) {
            if ($now < $time) {
                $delta = $time - $now;

                if ($next > $delta) {
                    $next = $delta;
                }

                continue;
            }

            if (isset($this->runningTasks[$id])) {
                $this->logger?->warning('Task {task} is still running, deferring execution', ['task' => $this->tasks[$id]->name()]);

                if ($next > 10) {
                    $next = 10;
                }

                continue;
            }

            $task = $this->tasks[$id];
            $this->runTask($task);

            if (!$task->perpetual()) {
                unset($this->taskTime[$id]);
                continue;
            }

            $time = $task->delay()->toMilliseconds($now);
            $this->taskTime[$id] = $time;
            $delta = $time - $now;

            if ($next > $delta) {
                $next = $delta;
            }
        }

        return max($next, 1);
    }

    /**
     * Run the task in the background using a fork.
     */
    private function runTask(ScheduledTaskInterface $task): void
    {
        $this->logger?->info('Running task {task}', ['task' => $task->name()]);

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Fork failed');
        }

        if ($pid !== 0) {
            $this->logger?->info('Task {task} is running in background with PID {pid}', ['task' => $task->name(), 'pid' => $pid]);
            $this->runningTasks[spl_object_id($task)] = $pid;
            return;
        }

        try {
            $result = $task->run();
        } catch (Throwable $e) {
            $this->logger?->error('Task {task} failed with exception: {exception}', ['task' => $task->name(), 'exception' => $e]);
            $result = false;
        }

        exit($result ? 0 : 1);
    }

    /**
     * Check if any of the running tasks have terminated and remove them from the list of running tasks.
     *
     * @return bool true if all tasks are terminated, false if there are still running tasks
     */
    private function checkTerminated(): bool
    {
        foreach ($this->runningTasks as $id => $pid) {
            $result = pcntl_waitpid($pid, $status, WNOHANG);

            assert($result !== -1 && is_int($status));

            // The child process is still running
            if ($result === 0) {
                continue;
            }

            unset($this->runningTasks[$id]);

            $task = $this->tasks[$id];
            $status = pcntl_wexitstatus($status);
            $this->logger?->info('Task {task} with PID {pid} has terminated with status {status}', ['task' => $task->name(), 'pid' => $pid, 'status' => $status]);

            if (!$task->perpetual()) {
                unset($this->tasks[$id]);
            }
        }

        return $this->runningTasks === [];
    }

    /**
     * Get the current time in milliseconds.
     * This value has an arbitrary start and may not correspond to a UNIX timestamp.
     */
    private function now(): int
    {
        return (int) (hrtime(true) / 1_000_000);
    }
}
