<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Console;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\Console\MigrationStatusCommand;
use Arakne\Spinneret\Database\Migration\Console\MigrationUpCommand;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\TestMigrationApp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationUpCommandTest extends TestCase
{
    private TestMigrationApp $app;
    private MigrationUpCommand $command;
    private MigrationManager $migration;
    private DatabaseConnectionManagerInterface $database;

    protected function setUp(): void
    {
        $this->app = new TestMigrationApp();
        $this->command = $this->app->get(MigrationUpCommand::class);
        $this->migration = $this->app->get('migration_manager');
        $this->database = $this->app->get('database');
    }

    #[Test]
    public function executeSuccess()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $out = $tester->getDisplay(true);
        $this->assertMatchesRegularExpression($this->outputToRegex(file_get_contents(__DIR__.'/../Fixtures/output/up-all')), $out);

        $this->assertSame('1.2.0', $this->migration->currentVersion());

        $this->assertEquals([
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith', 'birth_date' => '1991-02-21'],
            ['id' => 2, 'first_name' => 'Bob', 'last_name' => 'Johnson', 'birth_date' => '1992-03-22'],
            ['id' => 3, 'first_name' => 'Charlie', 'last_name' => 'Brown', 'birth_date' => '1993-04-23'],
        ], $this->database->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }

    #[Test]
    public function executeMigrated()
    {
        $this->migration->up();

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $out = $tester->getDisplay(true);
        $this->assertSame(file_get_contents(__DIR__.'/../Fixtures/output/up-migrated'), $out);
    }

    private function outputToRegex(string $output): string
    {
        return '/^'.strtr(preg_quote($output), ['\.\*' => '.*']).'$/';
    }
}
