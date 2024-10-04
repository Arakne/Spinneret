<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\QueryBuildingException;
use Arakne\Spinneret\Database\Exception\QueryExecutionException;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QueryStatementTest extends TestCase
{
    private DatabaseConnection $connection;
    private ArrayLogger $logger;

    protected function setUp(): void
    {
        $this->connection = new DatabaseConnection(
            new ConnectionConfig(
                'test',
                'sqlite::memory:'
            ),
            $this->logger = new ArrayLogger(),
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

    #[Test]
    public function executeShouldReuseStatementIfNotChanged()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id = ?');
        $this->assertSame($stmt, $stmt->pushInt(1));

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
        $this->logger->logs = [];

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute reused prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT * FROM test WHERE id = ?',
                    'parameters' => [[1, 1]],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function executeShouldReuseStatementIfNotChangedEvenWithArray()
    {
        $stmt = $this->connection->prepare('SELECT name FROM test WHERE id IN (...?) AND name LIKE ?');
        $this->assertSame($stmt, $stmt->pushArrayOfInt([1, 3]));
        $stmt->pushString('b%');

        $this->assertSame(['baz'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        $this->assertSame(['baz'], $stmt->execute()->asColumns(0));
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute reused prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id IN (?, ?) AND name LIKE ?',
                    'parameters' => [[1, 1], [3, 1], ['b%', 2]],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function executeReusedStatementConnectionLostAutoReconnect()
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
            ),
            $this->logger,
        );

        $connection->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT)');
        $connection->exec('TRUNCATE TABLE test');
        $connection->exec('INSERT INTO test (id, name) VALUES (1, "foo")');
        $connection->exec('SET SESSION wait_timeout=1');

        $stmt = $connection->prepare('SELECT name FROM test WHERE id = ?');
        $stmt->pushInt(1);
        $this->assertEquals(['foo'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        sleep(2);
        $this->assertEquals(['foo'], $stmt->execute()->asColumns(0));

        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute reused prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id = ?',
                    'parameters' => [[1, 1]],
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Reconnect to database {{ dsn }}',
                'context' => [
                    'dsn' => 'mysql:host=db;dbname=test',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Connect to database {{ dsn }}',
                'context' => [
                    'dsn' => 'mysql:host=db;dbname=test',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id = ?',
                    'parameters' => [[1, 1]],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function executeCannotReuseStatementIfExpressionChange()
    {
        $stmt = $this->connection->prepare('SELECT name FROM test WHERE {filter}');
        $stmt->setExpression('filter', '1 = 1');

        $this->assertSame(['foo', 'bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        $stmt->setExpression('filter', 'id > 1');

        $this->assertSame(['bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id > 1',
                    'parameters' => [],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function executeCannotReuseStatementIfExpressionAdded()
    {
        $stmt = $this->connection->prepare('SELECT name FROM test WHERE {filter}');
        $stmt->pushExpression('filter', '1 = 1');

        $this->assertSame(['foo', 'bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        $stmt->pushExpression('filter', 'id > 1', ' AND ');

        $this->assertSame(['bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE 1 = 1 AND id > 1',
                    'parameters' => [],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function resetAndReuseSimpleQuery()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id = ?');
        $this->assertSame($stmt, $stmt->pushInt(1));

        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
        $this->logger->logs = [];

        $stmt->reset();
        $stmt->pushInt(2);

        $this->assertSame([['id' => 2, 'name' => 'bar']], $stmt->execute()->asAssociativeArray());
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute reused prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT * FROM test WHERE id = ?',
                    'parameters' => [[2, 1]],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function resetWithArrayShouldBeRebuild()
    {
        $stmt = $this->connection->prepare('SELECT name FROM test WHERE id IN (...?)');
        $stmt->pushArrayOfInt([1, 3]);

        $this->assertSame(['foo', 'baz'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        $stmt->reset();
        $stmt->pushArrayOfInt([2, 3]);

        $this->assertSame(['bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id IN (?, ?)',
                    'parameters' => [[2, 1], [3, 1]],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function resetWithExpressionShouldBeRebuild()
    {
        $stmt = $this->connection->prepare('SELECT name FROM test WHERE {filter}');
        $stmt->setExpression('filter', '1 = 1');

        $this->assertSame(['foo', 'bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->logger->logs = [];

        $stmt->reset();
        $stmt->setExpression('filter', 'id > 1');

        $this->assertSame(['bar', 'baz'], $stmt->execute()->asColumns(0));
        $this->assertSame([
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT name FROM test WHERE id > 1',
                    'parameters' => [],
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function setInt()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id = ?');

        $this->assertSame($stmt, $stmt->setInt(0, 1));
        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
        $this->assertSame([['id' => 2, 'name' => 'bar']], $stmt->setInt(0, 2)->execute()->asAssociativeArray());
    }

    #[Test]
    public function setString()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name = ?');

        $this->assertSame($stmt, $stmt->setString(0, 'foo'));
        $this->assertSame([['id' => 1, 'name' => 'foo']], $stmt->execute()->asAssociativeArray());
        $this->assertSame([['id' => 2, 'name' => 'bar']], $stmt->setString(0, 'bar')->execute()->asAssociativeArray());
    }

    #[Test]
    public function setBool()
    {
        $this->connection->exec('CREATE TABLE test_bool (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, active INTEGER)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("foo", 1)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("bar", 0)');
        $this->connection->exec('INSERT INTO test_bool (name, active) VALUES ("baz", 1)');

        $stmt = $this->connection->prepare('SELECT * FROM test_bool WHERE active = ?');
        $this->assertSame($stmt, $stmt->setBool(0, true));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo', 'active' => 1],
            ['id' => 3, 'name' => 'baz', 'active' => 1],
        ], $stmt->execute()->asAssociativeArray());

        $this->assertSame($stmt, $stmt->setBool(0, false));

        $this->assertSame([
            ['id' => 2, 'name' => 'bar', 'active' => 0],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function setNull()
    {
        $stmt = $this->connection->prepare('INSERT INTO test (id, name) VALUES (?, ?)');
        $this->assertSame($stmt, $stmt
            ->pushInt(4)
            ->pushString('qux')
        );

        $stmt->executeUpdate();

        $this->assertSame($stmt, $stmt->setNull(0));
        $this->assertSame('5', $stmt->executeWithGeneratedKey());

        $this->assertSame([['id' => 5, 'name' => 'qux']], $this->connection->query('SELECT * FROM test WHERE id = 5')->asAssociativeArray());
    }

    #[Test]
    public function setArrayOfInt()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE id IN (...?)');
        $this->assertSame($stmt, $stmt->setArrayOfInt(0, [1, 3]));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 3, 'name' => 'baz'],
        ], $stmt->execute()->asAssociativeArray());

        $this->assertSame($stmt, $stmt->setArrayOfInt(0, [2]));
        $this->assertSame([
            ['id' => 2, 'name' => 'bar'],
        ], $stmt->execute()->asAssociativeArray());
    }

    #[Test]
    public function setArrayOfString()
    {
        $stmt = $this->connection->prepare('SELECT * FROM test WHERE name IN (...?)');
        $this->assertSame($stmt, $stmt->setArrayOfString(0, ['foo', 'baz']));

        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 3, 'name' => 'baz'],
        ], $stmt->execute()->asAssociativeArray());

        $this->assertSame(
            [
                ['id' => 2, 'name' => 'bar'],
            ],
            $stmt
                ->setArrayOfString(0, ['bar'])
                ->execute()
                ->asAssociativeArray()
        );
    }
}
