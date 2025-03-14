<?php

namespace Arakne\Spinneret\Database\Exception;

use Override;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * An error has occurs during the database connection
 */
final class DatabaseConnectionException extends RuntimeException implements DatabaseExceptionInterface
{
    public function __construct(
        public readonly string|UnitEnum $connection,
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
