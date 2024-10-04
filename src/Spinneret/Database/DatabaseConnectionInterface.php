<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseExceptionInterface;
use UnitEnum;

/**
 * Base type for interacting with a database
 *
 * @template C
 */
interface DatabaseConnectionInterface
{
    /**
     * Get the connection name
     */
    public function name(): string|UnitEnum;

    /**
     * The database driver name (e.g. "mysql", "sqlite", ...)
     */
    public function driver(): string;

    /**
     * Execute a read query and return the result
     *
     * Note: this method must not be used for parameterized queries
     *
     * @param string $query The SQL query to execute. This value must not be user-provided.
     *
     * @return QueryResult
     *
     * @throws DatabaseExceptionInterface
     *
     * @see DatabaseConnectionInterface::exec() For write queries
     * @see DatabaseConnectionInterface::prepare() For parameterized queries
     */
    public function query(string $query): QueryResult;

    /**
     * Execute a write query and return the number of affected rows
     *
     * Note: this method must not be used for parameterized queries
     *
     * @param string $query The SQL query to execute. This value must not be user-provided.
     *
     * @return int The number of affected rows
     * @throws DatabaseExceptionInterface
     *
     * @see DatabaseConnectionInterface::query() For read queries
     * @see DatabaseConnectionInterface::prepare() For parameterized queries
     */
    public function exec(string $query): int;

    /**
     * Prepare a parameterized query
     *
     * @param string $query The SQL query to prepare. This value must not be user-provided.
     *
     * @return QueryStatement
     * @throws DatabaseExceptionInterface
     */
    public function prepare(string $query): QueryStatement;

    /**
     * Get the internal connection object
     * The return type is implementation-specific
     *
     * This method must not be called outside the database module
     *
     * @return C
     * @throws DatabaseExceptionInterface
     * @internal
     */
    public function internalConnection(): mixed;

    /**
     * Reset the connection and create a new one
     *
     * The method is called automatically if {@see ConnectionConfig::$autoReconnect} is true and the connection is lost
     * during a query execution.
     *
     * Do not call this method manually unless you know what you are doing.
     *
     * @throws DatabaseExceptionInterface
     */
    public function reconnect(): void;
}
