<?php

namespace Arakne\Spinneret\Database\Exception;

use Override;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * Exception thrown when a query execution fails
 */
class QueryExecutionException extends RuntimeException implements DatabaseExceptionInterface
{
    public function __construct(
        public readonly string|UnitEnum $connection,
        public readonly string $query,
        public readonly array $parameters,
        public readonly ?array $errorInfo,
        string $message = "",
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    #[Override]
    public function connection(): string|UnitEnum
    {
        return $this->connection;
    }
}
