<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Console;

use Arakne\Spinneret\Database\Migration\Console\MigrationStatusCommand;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\TestMigrationApp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationStatusCommandTest extends TestCase
{
    private TestMigrationApp $app;
    private MigrationStatusCommand $command;
    private MigrationManager $migration;

    protected function setUp(): void
    {
        $this->app = new TestMigrationApp();
        $this->command = $this->app->get(MigrationStatusCommand::class);
        $this->migration = $this->app->get('migration_manager');
    }

    #[Test]
    public function executeNotMigrated()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $out = $tester->getDisplay(true);
        $this->assertSame(file_get_contents(__DIR__.'/../Fixtures/output/status-empty'), $out);
    }

    #[Test]
    public function executeMigrated()
    {
        $this->migration->up();

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $out = $tester->getDisplay(true);
        $this->assertSame(file_get_contents(__DIR__.'/../Fixtures/output/status-migrated'), $out);
    }
}
