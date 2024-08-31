<?php

namespace Arakne\Spinneret\Database;

// @todo interface ?
use PDO;

final class DatabaseConnection
{
    private ?PDO $connection;

    public function __construct(
        private readonly ConnectionConfig $config,
    ) {
    }

    public function query(string $query): QueryResult
    {
        // @todo handle return false
        return new QueryResult($this->internalConnection()->query($query));
    }

    public function prepare(string $query): QueryStatement
    {
        return new QueryStatement($this, $query);
    }

    /**
     * @return PDO
     * @internal
     */
    public function internalConnection(): PDO
    {
        return $this->connection ??= new PDO($this->config->dsn, $this->config->username, $this->config->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
}
