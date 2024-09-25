<?php

namespace Arakne\Tests\Spinneret\Database\Exception;

use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Arakne\Spinneret\Database\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseExceptionFactoryTest extends TestCase
{
    #[Test]
    public function uniqueConstraintErrorSqlite()
    {
        $e = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: test.name');
        $e->errorInfo = ['23000', 19, 'UNIQUE constraint failed: test.name'];

        $exception = DatabaseExceptionFactory::fromQueryExecution($e, 'test', 'INSERT INTO test (name) VALUES ("foo")');

        $this->assertInstanceOf(UniqueConstraintViolationException::class, $exception);
        $this->assertSame('test', $exception->connection());
        $this->assertSame('test', $exception->connection);
        $this->assertSame('name', $exception->key);
        $this->assertSame('INSERT INTO test (name) VALUES ("foo")', $exception->query);
    }
    #[Test]
    public function uniqueConstraintErrorMysql()
    {
        $e = new \PDOException('SQLSTATE[23000]: Duplicate entry \'foo\' for key \'name\'');
        $e->errorInfo = ['23000', 1062, 'Duplicate entry \'foo\' for key \'name\''];

        $exception = DatabaseExceptionFactory::fromQueryExecution($e, 'test', 'INSERT INTO test (name) VALUES ("foo")');

        $this->assertInstanceOf(UniqueConstraintViolationException::class, $exception);
        $this->assertSame('test', $exception->connection());
        $this->assertSame('test', $exception->connection);
        $this->assertSame('name', $exception->key);
        $this->assertSame('INSERT INTO test (name) VALUES ("foo")', $exception->query);
    }

    #[Test]
    public function connectionLost()
    {
        $e = new \PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');
        $e->errorInfo = ['HY000', 2006, 'MySQL server has gone away'];

        $exception = DatabaseExceptionFactory::fromQueryExecution($e, 'test', 'SELECT * FROM test');

        $this->assertInstanceOf(DatabaseConnectionLostException::class, $exception);
        $this->assertSame('test', $exception->connection());
        $this->assertSame('test', $exception->connection);
        $this->assertEquals('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away', $exception->getMessage());
        $this->assertSame($e, $exception->getPrevious());
    }


}
