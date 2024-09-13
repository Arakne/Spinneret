<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Override;
use PDO;
use PDOException;
use UnitEnum;

/**
 * Simple implementation of a database connection
 * Wrap an internal PDO connection
 *
 * @implements DatabaseConnectionInterface<PDO>
 * @todo handle reconnect + error handling
 */
final class DatabaseConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly ConnectionConfig $config,
    ) {
    }

    #[Override]
    public function name(): string|UnitEnum
    {
        return $this->config->name;
    }

    #[Override]
    public function query(string $query): QueryResult
    {
        try {
            return new QueryResult($this->internalConnection()->query($query));
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromQueryExecution($e, $this->name(), $query);
        }
    }

    #[Override]
    public function exec(string $query): int
    {
        try {
            return $this->internalConnection()->exec($query);
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromQueryExecution($e, $this->name(), $query);
        }
    }

    #[Override]
    public function prepare(string $query): QueryStatement
    {
        return new QueryStatement($this, $query);
    }

    #[Override]
    public function internalConnection(): PDO
    {
        try {
            return $this->connection ??= new PDO($this->config->dsn, $this->config->username, $this->config->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            throw $e; // @todo handle exception
        }
    }
}
