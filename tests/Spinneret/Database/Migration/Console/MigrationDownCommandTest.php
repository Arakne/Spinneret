<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Console;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\Console\MigrationDownCommand;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\TestMigrationApp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationDownCommandTest extends TestCase
{
    private TestMigrationApp $app;
    private MigrationDownCommand $command;
    private MigrationManager $migration;
    private DatabaseConnectionManagerInterface $database;

    protected function setUp(): void
    {
        $this->app = new TestMigrationApp();
        $this->command = $this->app->get(MigrationDownCommand::class);
        $this->migration = $this->app->get('migration_manager');
        $this->database = $this->app->get('database');
    }

    #[Test]
    public function executeMissingArgs()
    {
        $tester = new CommandTester($this->command);
        $this->assertSame(1, $tester->execute([]));

        $out = $tester->getDisplay(true);
        $this->assertEquals(file_get_contents(__DIR__.'/../Fixtures/output/down-missing-arg'), $out);
    }

    #[Test]
    public function executeNoMigrationApplied()
    {
        $tester = new CommandTester($this->command);
        $this->assertSame(0, $tester->execute(['--until' => '1.0.0']));

        $out = $tester->getDisplay(true);
        $this->assertEquals(file_get_contents(__DIR__.'/../Fixtures/output/down-no-migration-applied'), $out);
    }

    #[Test]
    public function executeSuccessUntilOption()
    {
        $this->migration->up();

        $tester = new CommandTester($this->command);
        $tester->execute(['--until' => '1.0.1']);

        $out = $tester->getDisplay(true);
        $this->assertMatchesRegularExpression($this->outputToRegex(file_get_contents(__DIR__.'/../Fixtures/output/down-until-option')), $out);

        $this->assertSame('1.0.1', $this->migration->currentVersion());
        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Alice Smith', 'birth_date' => '1991-02-21'],
                ['id' => 2, 'name' => 'Bob Johnson', 'birth_date' => '1992-03-22'],
                ['id' => 3, 'name' => 'Charlie Brown', 'birth_date' => '1993-04-23'],
            ],
            $this->database->get('test')->query('SELECT * FROM `person`')->asAssociativeArray()
        );
    }

    #[Test]
    public function executeSuccessMigrationNameArgument()
    {
        $this->migration->up();

        $tester = new CommandTester($this->command);
        $tester->execute(['migrations' => ['AddEntitiesMigration']]);

        $out = $tester->getDisplay(true);
        $this->assertMatchesRegularExpression($this->outputToRegex(file_get_contents(__DIR__.'/../Fixtures/output/down-migration-argument')), $out);
        $this->assertSame('1.2.0', $this->migration->currentVersion());
        $this->assertEquals([], $this->database->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }

    #[Test]
    public function executeForceOption()
    {
        $this->migration->up();
        $this->database->get('test')->exec('DROP TABLE `MIGRATION_STATUS`');

        $tester = new CommandTester($this->command);
        $tester->execute(['--until' => '1.0.1', '--force' => true]);

        $out = $tester->getDisplay(true);
        $this->assertMatchesRegularExpression($this->outputToRegex(file_get_contents(__DIR__.'/../Fixtures/output/down-until-option')), $out);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Alice Smith', 'birth_date' => '1991-02-21'],
                ['id' => 2, 'name' => 'Bob Johnson', 'birth_date' => '1992-03-22'],
                ['id' => 3, 'name' => 'Charlie Brown', 'birth_date' => '1993-04-23'],
            ],
            $this->database->get('test')->query('SELECT * FROM `person`')->asAssociativeArray()
        );
    }

    private function outputToRegex(string $output): string
    {
        return '/^'.strtr(preg_quote($output), ['\.\*' => '.*']).'$/';
    }
}
