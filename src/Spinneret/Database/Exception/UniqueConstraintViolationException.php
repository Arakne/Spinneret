<?php

namespace Arakne\Spinneret\Database\Exception;

use Throwable;
use UnitEnum;

/**
 * Exception thrown when a unique constraint violation occurs (e.g. insert with duplicate key)
 */
final class UniqueConstraintViolationException extends QueryExecutionException
{
    /**
     * @param list<mixed> $parameters
     * @param array<array-key, mixed>|null $errorInfo
     */
    public function __construct(
        /**
         * The key that caused the violation
         * This value may be the index name or the column name, depending on the database system.
         */
        public readonly ?string $key,
        UnitEnum|string $connection,
        string $query,
        array $parameters,
        ?array $errorInfo,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($connection, $query, $parameters, $errorInfo, $message, $previous);
    }
}
