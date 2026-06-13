<?php

namespace Arakne\Tests\Spinneret\Scheduler\Console;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Console\Console;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Scheduler\Console\StartSchedulerCommand;
use Arakne\Spinneret\Scheduler\Locator\ScheduledTaskRegistry;
use Arakne\Spinneret\Scheduler\SchedulerModule;
use Arakne\Tests\Spinneret\Scheduler\Fixtures\SimpleTask;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function apcu_fetch;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function microtime;

class StartSchedulerCommandTest extends TestCase
{
    private Application $app;
    private Command $command;

    protected function setUp(): void
    {
        $this->app = new class(isDev: true, env: 'test-scheduler-command') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                    new SchedulerModule(),
                    new class implements ModuleInterface {
                        #[Override]
                        public function register(ContainerBuilder $containerBuilder): void
                        {
                            $containerBuilder->import(__DIR__ . '/../Fixtures/', 'Arakne\Tests\Spinneret\Scheduler\Fixtures');
                        }
                    }
                ];
            }
        };

        $this->command = $this->app->get(Console::class)->get('scheduler:start');
    }

    #[Test]
    public function executeSuccess()
    {
        $start = microtime(true);
        $tester = new CommandTester($this->command);
        $tester->execute(['--timeout' => 200]);

        $this->assertEqualsWithDelta(200, (microtime(true) - $start) * 1000, 20);
        $this->assertEqualsWithDelta(100, (apcu_fetch(SimpleTask::class) - $start) * 1000, 20);

        $this->assertEquals([
            '[INFO] Starting scheduler with 4 task(s):',
            '* simple-task (every 100 ms)',
            '* invokable-service (every 15 min)',
            '* foo (every 1 h)',
            '* bar (in 1 h)',
        ], array_values(array_filter(array_map(trim(...), explode(PHP_EOL, $tester->getDisplay(true))))));
    }

    #[Test]
    public function executeWithoutTasks()
    {
        $tester = new CommandTester(new StartSchedulerCommand(new ScheduledTaskRegistry([]), null));
        $tester->execute([]);

        $this->assertEquals([
            '[WARNING] No scheduled tasks found.',
        ], array_values(array_filter(array_map(trim(...), explode(PHP_EOL, $tester->getDisplay(true))))));
    }
}
