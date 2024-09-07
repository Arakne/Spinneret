<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryResultTest extends TestCase
{
    private DatabaseConnection $connection;

    protected function setUp(): void
    {
        $this->connection = new DatabaseConnection(new ConnectionConfig('test', 'sqlite::memory:'));
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
    public function asAssociativeArray()
    {
        $result = $this->connection->query('SELECT * FROM test')->asAssociativeArray();

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ], $result);
    }

    #[Test]
    public function asColumns()
    {
        $this->assertSame([1, 2, 3], $this->connection->query('SELECT id, name FROM test')->asColumns(0));
        $this->assertSame(['foo', 'bar', 'baz'], $this->connection->query('SELECT id, name FROM test')->asColumns(1));
    }

    #[Test]
    public function mapAssociativeArray()
    {
        $this->assertSame(
            ['foo', 'bar', 'baz'],
            $this->connection->query('SELECT * FROM test')->mapAssociativeArray(fn (array $row) => $row['name'])
        );
    }

    #[Test]
    public function fetchAssociativeArray()
    {
        $result = $this->connection->query('SELECT * FROM test');

        $this->assertSame(['id' => 1, 'name' => 'foo'], $result->fetchAssociativeArray());
        $this->assertSame(['id' => 2, 'name' => 'bar'], $result->fetchAssociativeArray());
        $this->assertSame(['id' => 3, 'name' => 'baz'], $result->fetchAssociativeArray());
        $this->assertFalse($result->fetchAssociativeArray());
    }

    #[Test]
    public function fetchColum()
    {
        $result = $this->connection->query('SELECT id, name FROM test');

        $this->assertSame(1, $result->fetchColumn(0));
        $this->assertSame('bar', $result->fetchColumn(1));
        $this->assertSame(3, $result->fetchColumn(0));
        $this->assertFalse($result->fetchColumn(0));
    }
}
