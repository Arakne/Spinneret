<?php

namespace Arakne\Spinneret\Database;

use Override;
use PDO;

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
    public function query(string $query): QueryResult
    {
        // @todo handle return false
        return new QueryResult($this->internalConnection()->query($query));
    }

    #[Override]
    public function exec(string $query): int
    {
        // @todo handle return false
        return $this->internalConnection()->exec($query);
    }

    #[Override]
    public function prepare(string $query): QueryStatement
    {
        return new QueryStatement($this, $query);
    }

    #[Override]
    public function internalConnection(): PDO
    {
        return $this->connection ??= new PDO($this->config->dsn, $this->config->username, $this->config->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
}
