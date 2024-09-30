<?php

namespace Arakne\Tests\Spinneret\Console;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\CacheClearCommand;
use Arakne\Spinneret\Console\ConsoleModule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CacheClearCommandTest extends TestCase
{
    #[Test]
    public function exec()
    {
        $app = new class(true, 'test') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                ];
            }
        };

        $tester = new CommandTester(new CacheClearCommand($app));
        $this->assertSame(0, $tester->execute([]));
        $this->assertStringContainsString("[OK] Cache cleared.", $tester->getDisplay(true));

        $this->assertDirectoryDoesNotExist($app->cacheDir());

        $this->assertSame(0, $tester->execute([]));
        $this->assertStringContainsString("[WARNING] Cache directory does not exist.", $tester->getDisplay(true));
    }
}
