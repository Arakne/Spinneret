<?php

namespace Arakne\Spinneret\Database\Exception;

use Override;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * Exception thrown when the database connection is lost
 * The connection should be re-established before continuing
 */
final class DatabaseConnectionLostException extends RuntimeException implements DatabaseExceptionInterface
{
    public function __construct(
        public readonly string|UnitEnum $connection,
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
