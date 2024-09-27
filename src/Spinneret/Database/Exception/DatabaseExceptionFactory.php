<?php

namespace Arakne\Spinneret\Database\Exception;

use PDOException;
use UnitEnum;

use function preg_match;
use function str_starts_with;
use function strrpos;
use function substr;
use function trim;

/**
 * Create the appropriate exception for a database error
 */
final class DatabaseExceptionFactory
{
    private const array CONNECTION_LOST_ERRORS = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'Transaction() on null',
        'child connection forced to terminate due to client_idle_limit',
        'query_wait_timeout',
        'reset by peer',
        'Physical connection is not usable',
        'TCP Provider: Error code 0x68',
        'ORA-03114',
        'Packets out of order. Expected',
        'Adaptive Server connection failed',
        'Communication link failure',
    ];

    /**
     * An error occurred during query execution
     *
     * @param PDOException $e Base exception
     * @param string|UnitEnum $connection Connection name
     * @param string $query Executed query
     * @param list<mixed> $parameters Query parameters. Empty array if none.
     *
     * @return DatabaseExceptionInterface
     */
    public static function fromQueryExecution(PDOException $e, string|UnitEnum $connection, string $query, array $parameters = []): DatabaseExceptionInterface
    {
        $code = (string) ($e->errorInfo[0] ?? '');
        $message = (string) ($e->errorInfo[2] ?? '');

        // SQLite unique constraint violation
        if ($code === '23000' && str_starts_with($message, 'UNIQUE constraint failed:')) {
            $key = trim(substr($message, 28));

            if (($dot = strrpos($key, '.')) !== false) {
                $key = substr($key, $dot + 1);
            }

            return new UniqueConstraintViolationException($key, $connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
        }

        // MySQL unique constraint violation
        if ($code === '23000' && preg_match('/Duplicate entry \'(.*)\' for key \'(.*)\'/', $message, $matches)) {
            return new UniqueConstraintViolationException($matches[2], $connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
        }

        if (self::isConnectionLostError($message)) {
            return new DatabaseConnectionLostException($connection, $e->getMessage(), $e);
        }

        return new QueryExecutionException($connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
    }

    public static function fromDatabaseConnection(PDOException $exception, string|UnitEnum $connection): DatabaseExceptionInterface
    {
        return new DatabaseConnectionException(
            $connection,
            $exception->errorInfo,
            $exception->getMessage(),
            $exception
        );
    }

    private static function isConnectionLostError(string $message): bool
    {
        foreach (self::CONNECTION_LOST_ERRORS as $error) {
            if (stripos($message, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
