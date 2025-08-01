<?php

namespace Arakne\Tests\Spinneret\Console;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\CacheClearCommand;
use Arakne\Spinneret\Console\Console;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Console\DebugConfigCommand;
use Arakne\Tests\Spinneret\Console\Fixtures\CustomCommandModule;
use Arakne\Tests\Spinneret\Console\Fixtures\HelloCommand;
use Arakne\Tests\Spinneret\Console\Fixtures\ManualTagCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleTest extends TestCase
{
    #[Test]
    public function functionalDefaultCommands()
    {
        $app = new class(true, 'test') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }

            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                ];
            }
        };

        $console = $app->get(Console::class);

        $this->assertEquals(['help', 'list', '_complete', 'completion', 'cache:clear', 'clear:cache', 'debug:config'], array_keys($console->all()));

        $this->assertInstanceOf(CacheClearCommand::class, $console->get('cache:clear'));
        $this->assertInstanceOf(CacheClearCommand::class, $console->get('clear:cache'));
        $this->assertInstanceOf(DebugConfigCommand::class, $console->get('debug:config'));
    }

    #[Test]
    public function functionalCustomCommands()
    {
        $app = new class(true, 'test') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }

            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                    new CustomCommandModule(),
                ];
            }
        };

        $console = $app->get(Console::class);

        $this->assertInstanceOf(HelloCommand::class, $console->get('hello'));
        $this->assertInstanceOf(HelloCommand::class, $console->get('hi'));
        $this->assertInstanceOf(ManualTagCommand::class, $console->get('manual'));
    }

    #[Test]
    public function functionalRun()
    {
        $app = new class(true, 'test') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }

            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                    new CustomCommandModule(),
                ];
            }
        };

        $console = $app->get(Console::class);
        $console->setAutoExit(false);
        $this->assertSame(0, $console->run(new StringInput('hello'), $output = new BufferedOutput()));

        $this->assertSame('Hello world!'.PHP_EOL, $output->fetch());
    }
}
