<?php

namespace Arakne\Spinneret\Database;

/**
 * Base type for interacting with a database
 *
 * @template C
 */
interface DatabaseConnectionInterface
{
    /**
     * Execute a read query and return the result
     *
     * Note: this method must not be used for parameterized queries
     *
     * @param string $query The SQL query to execute. This value must not be user-provided.
     *
     * @return QueryResult
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
     */
    public function prepare(string $query): QueryStatement;

    /**
     * Get the internal connection object
     * The return type is implementation-specific
     *
     * This method must not be called outside the database module
     *
     * @return C
     * @internal
     */
    public function internalConnection(): mixed;
}
