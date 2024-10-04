<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Repository;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Migration\MigrationInterface;
use Arakne\Spinneret\Database\Migration\Repository\SqlMigrationRepository;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SqlMigrationRepositoryTest extends TestCase
{
    private SqlMigrationRepository $repository;
    private DatabaseConnection $db;

    protected function setUp(): void
    {
        $this->repository = new SqlMigrationRepository(
            $this->db = new DatabaseConnection(
                new ConnectionConfig(name: 'test', dsn: 'sqlite::memory:')
            ),
            FixedClock::instance(),
        );
    }

    #[Test]
    public function initialize()
    {
        $this->assertFalse($this->db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="MIGRATION_STATUS"')->fetchColumn(0));
        $this->repository->initialize();
        $this->assertSame('MIGRATION_STATUS', $this->db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="MIGRATION_STATUS"')->fetchColumn(0));
    }

    #[Test]
    public function isApplied()
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('name')->willReturn('test');
        $migration->method('version')->willReturn('1.0.0');
        $migration->method('date')->willReturn(new \DateTimeImmutable('2021-01-01 00:00:00'));

        $migration2 = $this->createMock(MigrationInterface::class);
        $migration2->method('name')->willReturn('test2');
        $migration2->method('version')->willReturn('1.0.1');
        $migration2->method('date')->willReturn(new \DateTimeImmutable('2021-01-02 00:00:00'));

        $this->assertFalse($this->repository->isApplied($migration));
        $this->repository->initialize();
        $this->assertFalse($this->repository->isApplied($migration));

        $this->repository->markAsApplied($migration);
        $this->assertTrue($this->repository->isApplied($migration));
        $this->assertFalse($this->repository->isApplied($migration2));

        $this->repository->remove($migration);
        $this->assertFalse($this->repository->isApplied($migration));
        $this->assertFalse($this->repository->isApplied($migration2));
    }

    #[Test]
    public function markAsApplied()
    {
        $this->repository->initialize();

        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('name')->willReturn('test');
        $migration->method('version')->willReturn('1.0.0');
        $migration->method('date')->willReturn(new \DateTimeImmutable('2021-01-01 00:00:00'));

        $this->repository->markAsApplied($migration);
        $this->assertTrue($this->repository->isApplied($migration));

        $this->assertEquals([
            [
                'MIGRATION_NAME' => 'test',
                'MIGRATION_DATE' => '2021-01-01 00:00:00',
                'VERSION' => '1.0.0',
                'APPLIED_AT' => '2024-09-03 18:49:29',
            ],
        ], $this->db->query('SELECT * FROM MIGRATION_STATUS')->asAssociativeArray());
    }

    #[Test]
    public function remove()
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('name')->willReturn('test');
        $migration->method('version')->willReturn('1.0.0');
        $migration->method('date')->willReturn(new \DateTimeImmutable('2021-01-01 00:00:00'));

        $migration2 = $this->createMock(MigrationInterface::class);
        $migration2->method('name')->willReturn('test2');
        $migration2->method('version')->willReturn('1.0.1');
        $migration2->method('date')->willReturn(new \DateTimeImmutable('2021-01-02 00:00:00'));

        $this->repository->remove($migration);
        $this->repository->initialize();
        $this->repository->remove($migration);

        $this->repository->markAsApplied($migration);
        $this->repository->markAsApplied($migration2);
        $this->repository->remove($migration);
        $this->assertFalse($this->repository->isApplied($migration));
        $this->assertTrue($this->repository->isApplied($migration2));

        $this->assertEquals([
            [
                'MIGRATION_NAME' => 'test2',
                'MIGRATION_DATE' => '2021-01-02 00:00:00',
                'VERSION' => '1.0.1',
                'APPLIED_AT' => '2024-09-03 18:49:29',
            ],
        ], $this->db->query('SELECT * FROM MIGRATION_STATUS')->asAssociativeArray());
    }

    #[Test]
    public function lastVersion()
    {
        $this->assertNull($this->repository->lastVersion());
        $this->repository->initialize();
        $this->assertNull($this->repository->lastVersion());

        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('name')->willReturn('test');
        $migration->method('version')->willReturn('1.0.0');
        $migration->method('date')->willReturn(new \DateTimeImmutable('2021-01-01 00:00:00'));

        $migration2 = $this->createMock(MigrationInterface::class);
        $migration2->method('name')->willReturn('test2');
        $migration2->method('version')->willReturn('1.0.1');
        $migration2->method('date')->willReturn(new \DateTimeImmutable('2021-01-02 00:00:00'));

        $this->repository->markAsApplied($migration);
        $this->repository->markAsApplied($migration2);

        $this->assertSame('1.0.1', $this->repository->lastVersion());
    }
}
