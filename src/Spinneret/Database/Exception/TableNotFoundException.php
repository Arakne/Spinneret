<?php

namespace Arakne\Spinneret\Database\Exception;

use Throwable;
use UnitEnum;

/**
 * Exception thrown when querying a table that does not exist
 */
final class TableNotFoundException extends QueryExecutionException
{
    public function __construct(
        /**
         * The requested table name or view name
         * This value may be null if the table name could not be determined
         */
        public readonly ?string $table,
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
