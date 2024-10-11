<?php

namespace Arakne\Tests\Spinneret\Database\Migration;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Arakne\Spinneret\Database\Migration\Repository\SqlMigrationRepository;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\AddEntitiesMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\CreateStructureMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\SeparateNameColumnsMigration;
use Arakne\Tests\Spinneret\Database\Migration\Fixtures\SkippedMigration;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigrationManagerTest extends TestCase
{
    private DatabaseConnectionManager $connections;
    private MigrationManager $migrations;
    private ArrayLogger $logger;

    protected function setUp(): void
    {
        $this->connections = new DatabaseConnectionManager(new DatabaseConfig(connections: [
            new ConnectionConfig(name: 'test', dsn: 'sqlite::memory:'),
        ]));
        $this->migrations = new MigrationManager(
            new SqlMigrationRepository(
                $this->connections->get('test'),
                FixedClock::instance(),
            ),
            $this->connections,
            function () {
                yield new AddEntitiesMigration();
                yield new CreateStructureMigration();
                yield new SeparateNameColumnsMigration();
                yield new SkippedMigration();
            },
            $this->logger = new ArrayLogger(),
        );
    }

    #[Test]
    public function listNoneApplied()
    {
        $list = $this->migrations->list();

        $this->assertCount(3, $list);

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertFalse($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertFalse($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertFalse($list[2]->applied);
    }

    #[Test]
    public function up()
    {
        $out = [];
        $count = $this->migrations->up(function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(3, $count);
        $this->assertCount(10, $out);
        $this->assertStringStartsWith('Applying migration CreateStructureMigration...', $out[0]);
        $this->assertStringStartsWith('Creating table `person`', $out[1]);
        $this->assertStringStartsWith('Migration CreateStructureMigration applied in ', $out[2]);
        $this->assertStringStartsWith('Applying migration AddEntitiesMigration...', $out[3]);
        $this->assertStringStartsWith('Migration AddEntitiesMigration applied in ', $out[4]);
        $this->assertStringStartsWith('Applying migration SeparateNameColumnsMigration...', $out[5]);
        $this->assertStringStartsWith('Processing person 1', $out[6]);
        $this->assertStringStartsWith('Processing person 2', $out[7]);
        $this->assertStringStartsWith('Processing person 3', $out[8]);
        $this->assertStringStartsWith('Migration SeparateNameColumnsMigration applied in ', $out[9]);

        $this->assertCount(3, $this->logger->logs);
        $this->assertEquals('info', $this->logger->logs[0]['level']);
        $this->assertEquals('Migration {{ migration }} applied in {{ time }} ms', $this->logger->logs[0]['message']);
        $this->assertEquals(['CreateStructureMigration', 'AddEntitiesMigration', 'SeparateNameColumnsMigration'], array_map(fn ($log) => $log['context']['migration'], $this->logger->logs));
        $this->assertEquals([CreateStructureMigration::class, AddEntitiesMigration::class, SeparateNameColumnsMigration::class], array_map(fn ($log) => $log['context']['migration_class'], $this->logger->logs));

        $list = $this->migrations->list();
        $this->assertCount(3, $list);
        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertTrue($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertTrue($list[2]->applied);

        $this->assertEquals([
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith', 'birth_date' => '1991-02-21'],
            ['id' => 2, 'first_name' => 'Bob', 'last_name' => 'Johnson', 'birth_date' => '1992-03-22'],
            ['id' => 3, 'first_name' => 'Charlie', 'last_name' => 'Brown', 'birth_date' => '1993-04-23'],
        ], $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());

        $this->assertSame('1.2.0', $this->migrations->currentVersion());

        $this->assertSame(0, $this->migrations->up());
    }

    #[Test]
    public function upPartial()
    {
        $this->migrations->up();
        $this->migrations->down(['CreateStructureMigration']);
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->up(function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(1, $count);
        $this->assertCount(3, $out);
        $this->assertStringStartsWith('Applying migration CreateStructureMigration...', $out[0]);
        $this->assertStringStartsWith('Creating table `person`', $out[1]);
        $this->assertStringStartsWith('Migration CreateStructureMigration applied in ', $out[2]);

        $this->assertCount(1, $this->logger->logs);
        $this->assertEquals('info', $this->logger->logs[0]['level']);
        $this->assertEquals('Migration {{ migration }} applied in {{ time }} ms', $this->logger->logs[0]['message']);
        $this->assertEquals(['CreateStructureMigration'], array_map(fn ($log) => $log['context']['migration'], $this->logger->logs));
        $this->assertEquals([CreateStructureMigration::class], array_map(fn ($log) => $log['context']['migration_class'], $this->logger->logs));

        $list = $this->migrations->list();
        $this->assertCount(3, $list);
        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertTrue($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertTrue($list[2]->applied);

        $this->assertSame('1.2.0', $this->migrations->currentVersion());
    }

    #[Test]
    public function upWithoutOutput()
    {
        $this->migrations->up();

        $list = $this->migrations->list();
        $this->assertCount(3, $list);
        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertTrue($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertTrue($list[2]->applied);

        $this->assertEquals([
            ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith', 'birth_date' => '1991-02-21'],
            ['id' => 2, 'first_name' => 'Bob', 'last_name' => 'Johnson', 'birth_date' => '1992-03-22'],
            ['id' => 3, 'first_name' => 'Charlie', 'last_name' => 'Brown', 'birth_date' => '1993-04-23'],
        ], $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());

        $this->assertSame('1.2.0', $this->migrations->currentVersion());
    }

    #[Test]
    public function downNotApplied()
    {
        $out = [];
        $count = $this->migrations->down(['AddEntitiesMigration'], false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(0, $count);
        $this->assertEmpty($out);
    }

    #[Test]
    public function down()
    {
        $this->migrations->up();
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->down(['AddEntitiesMigration'], false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(1, $count);

        $this->assertCount(2, $out);
        $this->assertStringStartsWith('Rolling back migration AddEntitiesMigration...', $out[0]);
        $this->assertStringStartsWith('Migration AddEntitiesMigration rolled back in ', $out[1]);

        $this->assertCount(1, $this->logger->logs);
        $this->assertEquals('info', $this->logger->logs[0]['level']);
        $this->assertEquals('Migration {{ migration }} rolled back in {{ time }} ms', $this->logger->logs[0]['message']);
        $this->assertEquals(['AddEntitiesMigration'], array_map(fn ($log) => $log['context']['migration'], $this->logger->logs));
        $this->assertEquals([AddEntitiesMigration::class], array_map(fn ($log) => $log['context']['migration_class'], $this->logger->logs));

        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertFalse($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertTrue($list[2]->applied);

        $this->assertEmpty($this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }

    #[Test]
    public function downWithoutOutput()
    {
        $this->migrations->up();

        $count = $this->migrations->down(['AddEntitiesMigration']);

        $this->assertSame(1, $count);
        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertFalse($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertTrue($list[2]->applied);

        $this->assertEmpty($this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }

    #[Test]
    public function downForce()
    {
        $this->migrations->up();
        $this->connections->get('test')->exec('DROP TABLE `MIGRATION_STATUS`');
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->down(['AddEntitiesMigration'], false, function ($line) use (&$out) {
            $out[] = $line;
        });
        $this->assertSame(0, $count);
        $this->assertEmpty($count);
        $this->assertEmpty($this->logger->logs);


        $count = $this->migrations->down(['AddEntitiesMigration'], true, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(1, $count);
        $this->assertCount(2, $out);
        $this->assertCount(1, $this->logger->logs);
        $this->assertStringStartsWith('Rolling back migration AddEntitiesMigration...', $out[0]);
        $this->assertStringStartsWith('Migration AddEntitiesMigration rolled back in ', $out[1]);

        $this->assertEmpty($this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }

    #[Test]
    public function rollbackNotApplied()
    {
        $out = [];
        $count = $this->migrations->rollback('1.0.0', false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(0, $count);
        $this->assertEmpty($out);

        $this->migrations->up();
        $count = $this->migrations->rollback('2.0.0', false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(0, $count);
        $this->assertEmpty($out);
    }

    #[Test]
    public function rollback()
    {
        $this->migrations->up();
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->rollback('1.0.1', false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(1, $count);

        $this->assertCount(5, $out);
        $this->assertStringStartsWith('Rolling back migration SeparateNameColumnsMigration...', $out[0]);
        $this->assertStringStartsWith('Processing person 1', $out[1]);
        $this->assertStringStartsWith('Processing person 2', $out[2]);
        $this->assertStringStartsWith('Processing person 3', $out[3]);
        $this->assertStringStartsWith('Migration SeparateNameColumnsMigration rolled back in', $out[4]);

        $this->assertCount(1, $this->logger->logs);
        $this->assertEquals('info', $this->logger->logs[0]['level']);
        $this->assertEquals('Migration {{ migration }} rolled back in {{ time }} ms', $this->logger->logs[0]['message']);
        $this->assertEquals(['SeparateNameColumnsMigration'], array_map(fn ($log) => $log['context']['migration'], $this->logger->logs));
        $this->assertEquals([SeparateNameColumnsMigration::class], array_map(fn ($log) => $log['context']['migration_class'], $this->logger->logs));

        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertTrue($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertFalse($list[2]->applied);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Alice Smith', 'birth_date' => '1991-02-21'],
                ['id' => 2, 'name' => 'Bob Johnson', 'birth_date' => '1992-03-22'],
                ['id' => 3, 'name' => 'Charlie Brown', 'birth_date' => '1993-04-23'],
            ],
            $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray()
        );
        $this->assertSame('1.0.1', $this->migrations->currentVersion());
    }

    #[Test]
    public function rollbackWithoutOutput()
    {
        $this->migrations->up();

        $count = $this->migrations->rollback('1.0.1');
        $this->assertSame(1, $count);
        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertTrue($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertFalse($list[2]->applied);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'Alice Smith', 'birth_date' => '1991-02-21'],
                ['id' => 2, 'name' => 'Bob Johnson', 'birth_date' => '1992-03-22'],
                ['id' => 3, 'name' => 'Charlie Brown', 'birth_date' => '1993-04-23'],
            ],
            $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray()
        );
        $this->assertSame('1.0.1', $this->migrations->currentVersion());
    }

    #[Test]
    public function rollbackMultiple()
    {
        $this->migrations->up();
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->rollback('1.0.0', false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(2, $count);
        $this->assertCount(7, $out);

        $this->assertCount(2, $this->logger->logs);
        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertInstanceOf(CreateStructureMigration::class, $list[0]->migration);
        $this->assertTrue($list[0]->applied);
        $this->assertInstanceOf(AddEntitiesMigration::class, $list[1]->migration);
        $this->assertFalse($list[1]->applied);
        $this->assertInstanceOf(SeparateNameColumnsMigration::class, $list[2]->migration);
        $this->assertFalse($list[2]->applied);

        $this->assertEquals([], $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
        $this->assertSame('1.0.0', $this->migrations->currentVersion());
    }

    #[Test]
    public function rollbackForce()
    {
        $this->migrations->up();
        $this->connections->get('test')->exec('DROP TABLE `MIGRATION_STATUS`');
        $this->logger->logs = [];

        $out = [];
        $count = $this->migrations->rollback('1.0.0', false, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(0, $count);
        $this->assertCount(0, $out);

        $count = $this->migrations->rollback('1.0.0', true, function ($line) use (&$out) {
            $out[] = $line;
        });

        $this->assertSame(2, $count);
        $this->assertCount(7, $out);

        $this->assertCount(2, $this->logger->logs);
        $this->assertCount(3, $list = $this->migrations->list());

        $this->assertEquals([], $this->connections->get('test')->query('SELECT * FROM `person`')->asAssociativeArray());
    }
}
