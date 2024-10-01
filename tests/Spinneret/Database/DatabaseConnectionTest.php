<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\Exception\DatabaseConnectionException;
use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\QueryExecutionException;
use Arakne\Spinneret\Database\Exception\UniqueConstraintViolationException;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
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

        $this->connection->exec('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->connection->exec('INSERT INTO test (name) VALUES ("foo")');
        $this->connection->exec('INSERT INTO test (name) VALUES ("bar")');
        $this->connection->exec('INSERT INTO test (name) VALUES ("baz")');

        $this->logger->logs = [];
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
        $this->assertEquals([
            [
                'level' => 'debug',
                'message' => 'Execute read query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT * FROM test ORDER BY id',
                ],
            ]
        ], $this->logger->logs);
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
        $this->assertEquals([
            [
                'level' => 'debug',
                'message' => 'Execute prepared query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT * FROM test WHERE name = ?',
                    'parameters' => [['foo', PDO::PARAM_STR]],
                ],
            ]
        ], $this->logger->logs);
    }

    #[Test]
    public function exec()
    {
        $this->assertSame(2, $this->connection->exec('UPDATE test SET name = "???" WHERE name LIKE "b%"'));
        $this->assertEquals([
            [
                'level' => 'debug',
                'message' => 'Execute write query "{{ query }}"',
                'context' => [
                    'query' => 'UPDATE test SET name = "???" WHERE name LIKE "b%"',
                ],
            ]
        ], $this->logger->logs);
        $this->assertSame([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => '???'],
            ['id' => 3, 'name' => '???'],
        ], $this->connection->query('SELECT * FROM test ORDER BY id')->asAssociativeArray());
    }

    #[Test]
    public function execSyntaxError()
    {
        try {
            $this->connection->exec('ALTER INDEX TABLE');
            $this->fail('Expected exception');
        } catch (QueryExecutionException $e) {
            $this->assertSame('test', $e->connection());
            $this->assertSame('ALTER INDEX TABLE', $e->query);
            $this->assertStringContainsString('near "INDEX": syntax error', $e->getMessage());
            $this->assertSame([], $e->parameters);
            $this->assertSame(['HY000', 1, 'near "INDEX": syntax error'], $e->errorInfo);
        }
    }

    #[Test]
    public function connectionError()
    {
        try {
            $connection = new DatabaseConnection(
                new ConnectionConfig(
                    'test',
                    'sqlite:/dev'
                )
            );
            $connection->query('CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
            $this->fail('Expected exception');
        } catch (DatabaseConnectionException $e) {
            $this->assertStringContainsString('unable to open database file', $e->getMessage());
            $this->assertSame('test', $e->connection());
            $this->assertSame(['HY000', 14, 'unable to open database file'], $e->errorInfo);
        }
    }

    #[Test]
    public function queryConnectionLost()
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

        $this->assertEquals(1, $connection->query('SELECT 1')->fetchColumn(0));
        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $connection->query('SELECT 1');
    }

    #[Test]
    public function queryConnectionLostWithAutoReconnect()
    {
        $connection = new DatabaseConnection(
            new ConnectionConfig(
                'reconnect',
                $dsn = 'mysql:host='.$_ENV['MYSQL_TEST_HOST'].';dbname='.$_ENV['MYSQL_TEST_DATABASE'],
                $_ENV['MYSQL_TEST_USER'],
                $_ENV['MYSQL_TEST_PASSWORD'],
                options: [
                    PDO::ATTR_PERSISTENT => false,
                ],
            ),
            $this->logger,
        );

        $this->assertEquals(1, $connection->query('SELECT 1')->fetchColumn(0));
        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $this->assertEquals(1, $connection->query('SELECT 1')->fetchColumn(0));
        $this->assertEquals([
            [
                'level' => 'debug',
                'message' => 'Execute read query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT 1',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Connect to database {{ dsn }}',
                'context' => [
                    'dsn' => $dsn,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Execute write query "{{ query }}"',
                'context' => [
                    'query' => 'SET SESSION wait_timeout=1',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Execute read query "{{ query }}"',
                'context' => [
                    'query' => 'SELECT 1',
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Reconnect to database {{ dsn }}',
                'context' => [
                    'dsn' => $dsn,
                ],
            ],
            [
                'level' => 'debug',
                'message' => 'Connect to database {{ dsn }}',
                'context' => [
                    'dsn' => $dsn,
                ],
            ],
        ], $this->logger->logs);
    }

    #[Test]
    public function execConnectionLost()
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

        sleep(2);
        $connection->exec('SELECT 1');
    }

    #[Test]
    public function execConnectionLostAutoReconnect()
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

        $connection->exec('SET SESSION wait_timeout=1');

        sleep(2);
        $this->assertSame(0, $connection->exec('SELECT 1'));
    }
}
