<?php

namespace Arakne\Spinneret\Database\Exception;

use PDOException;
use UnitEnum;

use function preg_match;
use function str_starts_with;
use function strrpos;
use function substr;
use function trim;

final class DatabaseExceptionFactory
{
    public static function fromQueryExecution(PDOException $e, string|UnitEnum $connection, string $query, array $parameters = []): QueryExecutionException
    {
        $code = $e->errorInfo[0] ?? null;
        $message = (string) ($e->errorInfo[2] ?? '');

        // SQLite unique constraint violation
        if ($code == '23000' && str_starts_with($message, 'UNIQUE constraint failed:')) {
            $key = trim(substr($message, 28));

            if (($dot = strrpos($key, '.')) !== false) {
                $key = substr($key, $dot + 1);
            }

            return new UniqueConstraintViolationException($key, $connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
        }

        // MySQL unique constraint violation
        if ($code == '23000' && preg_match('/Duplicate entry \'(.*)\' for key \'(.*)\'/', $message, $matches)) {
            return new UniqueConstraintViolationException($matches[2], $connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
        }

        return new QueryExecutionException($connection, $query, $parameters, $e->errorInfo, $e->getMessage(), $e);
    }
}
