<?php

namespace Arakne\Tests\Spinneret\Database\Schema;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Schema\SqliteSchema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SqliteSchemaTest extends TestCase
{
    private DatabaseConnection $conn;
    private SqliteSchema $schema;

    protected function setUp(): void
    {
        $this->schema = new SqliteSchema($this->conn = new DatabaseConnection(
            new ConnectionConfig(
                'test',
                'sqlite::memory:'
            )
        ));
    }

    #[Test]
    public function hasTable()
    {
        $this->assertFalse($this->schema->hasTable('test'));
        $this->conn->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->assertTrue($this->schema->hasTable('test'));
    }

    #[Test]
    public function hasIndex()
    {
        $this->assertFalse($this->schema->hasIndex('test', 'name'));

        $this->conn->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->assertFalse($this->schema->hasIndex('test', 'name'));

        $this->conn->exec('CREATE INDEX test_name ON test (name)');
        $this->assertTrue($this->schema->hasIndex('test', 'test_name'));
    }

    #[Test]
    public function hasColumn()
    {
        $this->assertFalse($this->schema->hasColumn('test', 'name'));
        $this->assertFalse($this->schema->hasColumn('`" -- OR 1 = 1', 'name'));

        $this->conn->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->assertTrue($this->schema->hasColumn('test', 'name'));
        $this->assertFalse($this->schema->hasColumn('test', 'other'));
    }

    #[Test]
    public function type()
    {
        $this->assertTrue($this->schema->isSqlite());
        $this->assertFalse($this->schema->isMySql());
    }
}
