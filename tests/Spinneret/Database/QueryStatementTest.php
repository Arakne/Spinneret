<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\QueryBuildingException;
use Arakne\Spinneret\Database\Exception\QueryExecutionException;
use PDO;
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

        $this->connection->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT) STRICT');
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
        $stmt = $this->connection->prepare('UPDATE test SET name = name || ? WHERE name LIKE ?');

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
    public function mixingArrayAndSimpleParameters()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id = ? OR name IN (...?)');

        $this->assertSame($stmt, $stmt
            ->pushInt(2)
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
        try {
            $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (?)');
            $this->assertSame($stmt, $stmt->pushArrayOfInt([1, 2]));
            $stmt->execute();

            $this->fail('Expected exception');
        } catch (QueryBuildingException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('SELECT * FROM test WHERE id IN (?)', $e->query);
            $this->assertSame('The spread placeholder `...?` must be present in the query when using an array parameter.', $e->getMessage());
        }
    }

    #[Test]
    public function executeSyntaxError()
    {
        try {
            $stmt = $this->connection->prepare('SELECT FROM test WHERE ?');
            $stmt->pushString('foo');
            $stmt->execute();
            $this->fail('Expected exception');
        } catch (QueryExecutionException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('SELECT FROM test WHERE ?', $e->query);
            $this->assertStringContainsString('near "FROM": syntax error', $e->getMessage());
            $this->assertSame(['foo'], $e->parameters);
            $this->assertSame(['HY000', 1, 'near "FROM": syntax error'], $e->errorInfo);
        }
    }

    #[Test]
    public function executeRuntimeError()
    {
        try {
            $stmt = $this->connection->prepare('INSERT INTO test (id, name) VALUES (?, ?)');
            $stmt->pushString('foo');
            $stmt->pushNull();
            $stmt->execute();
            $this->fail('Expected exception');
        } catch (QueryExecutionException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('INSERT INTO test (id, name) VALUES (?, ?)', $e->query);
            $this->assertStringContainsString('General error: 20 datatype mismatch', $e->getMessage());
            $this->assertSame(['foo', null], $e->parameters);
            $this->assertSame(['HY000', 20, 'datatype mismatch'], $e->errorInfo);
        }
    }

    #[Test]
    public function executeConnectionLost()
    {
        $this->expectException(DatabaseConnectionLostException::class);

        $connection = new DatabaseConnection(
            new ConnectionConfig(
                'reconnect',
                'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_PERSISTENT => false,
                ],
                autoReconnect: false,
            )
        );

        $connection->exec('SET SESSION wait_timeout=1');
        $connection->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)');
        $connection->exec('REPLACE INTO test (id, name) VALUES (1, "foo")');

        sleep(2);
        $stmt = $connection->prepare('SELECT * FROM test WHERE id = ?');
        $stmt->pushInt(1);
        $stmt->execute();
    }

    #[Test]
    public function executeConnectionLostAutoReconnect()
    {
        $connection = new DatabaseConnection(
            new ConnectionConfig(
                'reconnect',
                'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_PERSISTENT => false,
                ],
            )
        );

        $connection->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)');
        $connection->exec('REPLACE INTO test (id, name) VALUES (1, "foo")');
        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $stmt = $connection->prepare('SELECT * FROM test WHERE id = ?');
        $stmt->pushInt(1);
        $this->assertEquals([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function executeUpdateConnectionLostAutoReconnect()
    {
        $connection = new DatabaseConnection(
            new ConnectionConfig(
                'reconnect',
                'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_PERSISTENT => false,
                ],
            )
        );

        $connection->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)');
        $connection->exec('TRUNCATE TABLE test');
        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $stmt = $connection->prepare('REPLACE INTO test (id, name) VALUES (?, ?)');
        $stmt->pushInt(1);
        $stmt->pushString(bin2hex(random_bytes(16)));

        $this->assertSame(1, $stmt->executeUpdate());
    }

    #[Test]
    public function executeWithGeneratedKeyConnectionLostAutoReconnect()
    {
        $connection = new DatabaseConnection(
            new ConnectionConfig(
                'reconnect',
                'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_PERSISTENT => false,
                ],
            )
        );

        $connection->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)');
        $connection->exec('TRUNCATE TABLE test');
        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $stmt = $connection->prepare('REPLACE INTO test (name) VALUES (?)');
        $stmt->pushString(bin2hex(random_bytes(16)));

        $this->assertSame('1', $stmt->executeWithGeneratedKey());
    }
}
