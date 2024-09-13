<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Exception\QueryExecutionException;
use Arakne\Spinneret\Database\Exception\UniqueConstraintViolationException;
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
    public function querySyntaxError()
    {
        try {
            $this->connection->query('SELECT FROM test');
            $this->fail('Expected exception');
        } catch (QueryExecutionException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('SELECT FROM test', $e->query);
            $this->assertStringContainsString('near "FROM": syntax error', $e->getMessage());
            $this->assertSame([], $e->parameters);
            $this->assertSame(['HY000', 1, 'near "FROM": syntax error'], $e->errorInfo);
        }
    }

    #[Test]
    public function queryUniqueConstraintFail()
    {
        try {
            $this->connection->query('INSERT INTO test (id, name) VALUES (1, "foo")');
            $this->fail('Expected exception');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('INSERT INTO test (id, name) VALUES (1, "foo")', $e->query);
            $this->assertSame([], $e->parameters);
            $this->assertSame(['23000', 19, 'UNIQUE constraint failed: test.id'], $e->errorInfo);
            $this->assertStringContainsString('UNIQUE constraint failed: test.id', $e->getMessage());
            $this->assertSame('id', $e->key);
        }
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
