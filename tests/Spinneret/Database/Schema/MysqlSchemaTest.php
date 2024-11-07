<?php

namespace Arakne\Tests\Spinneret\Database\Schema;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Schema\MySqlSchema;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

class MysqlSchemaTest extends TestCase
{
    private DatabaseConnection $conn;
    private MySqlSchema $schema;

    protected function setUp(): void
    {
        $this->schema = new MySqlSchema($this->conn = new DatabaseConnection(
            new ConnectionConfig(
                'test',
                'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            )
        ));

        try {
            $this->conn->exec('DROP TABLE test_schema');
        } catch (Throwable) {
        }
    }

    protected function tearDown(): void
    {
        try {
            $this->conn->exec('DROP TABLE test_schema');
        } catch (Throwable) {
        }
    }

    #[Test]
    public function hasTable()
    {
        $this->assertFalse($this->schema->hasTable('test_schema'));
        $this->conn->exec('CREATE TABLE test_schema (id INTEGER PRIMARY KEY, name TEXT)');
        $this->assertTrue($this->schema->hasTable('test_schema'));

        $this->assertFalse($this->schema->hasTable('te "\'`st'));
    }

    #[Test]
    public function hasIndex()
    {
        $this->assertFalse($this->schema->hasIndex('test_schema', 'name'));

        $this->conn->exec('CREATE TABLE test_schema (id INTEGER PRIMARY KEY, name VARCHAR(32))');
        $this->assertFalse($this->schema->hasIndex('test_schema', 'name'));

        $this->conn->exec('CREATE INDEX test_name ON test_schema (name)');
        $this->assertTrue($this->schema->hasIndex('test_schema', 'test_name'));

        $this->assertFalse($this->schema->hasIndex('`" -- test', 'name'));
    }

    #[Test]
    public function hasColumn()
    {
        $this->assertFalse($this->schema->hasColumn('test_schema', 'name'));
        $this->assertFalse($this->schema->hasColumn('`" -- OR 1 = 1', 'name'));

        $this->conn->exec('CREATE TABLE test_schema (id INTEGER PRIMARY KEY, name TEXT)');
        $this->assertTrue($this->schema->hasColumn('test_schema', 'name'));
        $this->assertFalse($this->schema->hasColumn('test_schema', 'other'));
    }

    #[Test]
    public function type()
    {
        $this->assertFalse($this->schema->isSqlite());
        $this->assertTrue($this->schema->isMySql());
    }
}
