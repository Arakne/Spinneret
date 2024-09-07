<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    private DatabaseConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new DatabaseConnection(
            new ConnectionConfig(
                'test',
                'sqlite::memory:'
            )
        );

        $this->connection->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->connection->exec('INSERT INTO test (name) VALUES ("foo")');
        $this->connection->exec('INSERT INTO test (name) VALUES ("bar")');
        $this->connection->exec('INSERT INTO test (name) VALUES ("baz")');
    }

    protected function tearDown(): void
    {
        unset($this->connection);
    }

    #[Test]
    public function query()
    {
        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ], $this->connection->query('SELECT * FROM test ORDER BY id')->asAssociativeArray());
    }

    #[Test]
    public function prepare()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name = ?');
        $stmt->pushString('foo');

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function exec()
    {
        $this->assertSame(2, $this->connection->exec('UPDATE test SET name = "???" WHERE name LIKE "b%"'));
        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => '???'],
            ['id' => 3, 'name' => '???'],
        ], $this->connection->query('SELECT * FROM test ORDER BY id')->asAssociativeArray());
    }
}
