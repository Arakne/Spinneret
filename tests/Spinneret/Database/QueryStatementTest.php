<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryStatementTest extends TestCase
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
    public function pushInt()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id = ?');
        $this->assertSame($stmt, $stmt->pushInt(1));

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function pushString()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name = ?');
        $this->assertSame($stmt, $stmt->pushString('bar'));

        $this->assertSame([['id' => 2, 'name' => 'bar']], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function pushBool()
    {
        $this->connection->exec('CREATE TABLE test_bool (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("foo", 1)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("bar", 0)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("baz", 1)');

        $stmt = $this->connection->prepare('SELECT * FROM test_bool WHERE active = ?');
        $this->assertSame($stmt, $stmt->pushBool(true));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo', 'active' => 1],
            ['id' => 3, 'name' => 'baz', 'active' => 1],
        ], $stmt->execute()->asAssociativeArray());

        $stmt = $this->connection->prepare('SELECT * FROM test_bool WHERE active = ?');
        $this->assertSame($stmt, $stmt->pushBool(false));

        $this->assertSame([
            ['id' => 2, 'name' => 'bar', 'active' => 0],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function pushNull()
    {
        $stmt = $this->connection->prepare('INSERT INTO test (id, name) VALUES (?, ?)');
        $this->assertSame($stmt, $stmt
            ->pushNull()
            ->pushString('qux')
        );

        $this->assertSame('4', $stmt->executeWithGeneratedKey());

        $this->assertSame([['id' => 4, 'name' => 'qux']], $this->connection->query('SELECT * FROM test WHERE id = 4')->asAssociativeArray());
    }

    #[Test]
    public function pushArrayOfInt()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (...?)');
        $this->assertSame($stmt, $stmt->pushArrayOfInt([1, 3]));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 3, 'name' => 'baz'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function pushArrayOfString()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name IN (...?)');
        $this->assertSame($stmt, $stmt->pushArrayOfString(['foo', 'baz']));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 3, 'name' => 'baz'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function setExpression()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name = ? {other}');
        $this->assertSame($stmt, $stmt->pushString('foo'));

        $this->assertSame($stmt, $stmt->setExpression('other', 'AND id = ?'));
        $this->assertSame($stmt, $stmt->pushInt(1));

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());

        $this->assertSame($stmt, $stmt->setExpression('other', 'OR id = ? + 1'));
        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function pushExpression()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE {filters}');

        $this->assertSame($stmt, $stmt->pushExpression('filters', 'id < ?', ' AND '));
        $this->assertSame($stmt, $stmt->pushInt(3));

        $this->assertSame($stmt, $stmt->pushExpression('filters', 'name LIKE ?', ' AND '));
        $this->assertSame($stmt, $stmt->pushString('b%'));

        $this->assertSame([
            ['id' => 2, 'name' => 'bar'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function executeUpdate()
    {
        $stmt = $this->connection->prepare('UPDATE test SET name = concat(name, ?) WHERE name LIKE ?');

        $this->assertSame($stmt, $stmt
            ->pushString('-edit')
            ->pushString('b%')
        );

        $this->assertSame(2, $stmt->executeUpdate());

        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name LIKE ?');
        $this->assertSame($stmt, $stmt->pushString('b%'));
        $this->assertSame(0, $stmt->executeUpdate());
    }

    #[Test]
    public function executeWithGeneratedKey()
    {
        $stmt = $this->connection->prepare('INSERT INTO test (name) VALUES (?)');
        $stmt->pushString('qux');
        $this->assertSame('4', $stmt->executeWithGeneratedKey());
    }

    #[Test]
    public function expressionNotDefinedShouldBeIgnored()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name = ? {other}');
        $this->assertSame($stmt, $stmt->pushString('foo'));

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function multipleArrayFilters()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (...?) OR name IN (...?)');

        $this->assertSame($stmt, $stmt
            ->pushArrayOfInt([2, 4])
            ->pushArrayOfString(['foo', 'baz'])
        );

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function emptyArray()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (...?)');
        $this->assertSame($stmt, $stmt->pushArrayOfInt([]));

        $this->assertSame([], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function missingArrayPlaceholder()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The spread placeholder `...?` must be present in the query when using an array parameter.');

        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (?)');
        $this->assertSame($stmt, $stmt->pushArrayOfInt([1, 2]));

        $stmt->execute();
    }
}
