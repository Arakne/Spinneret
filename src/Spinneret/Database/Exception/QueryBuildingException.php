<?php

namespace Arakne\Spinneret\Database\Exception;

use LogicException;
use Override;
use Throwable;
use UnitEnum;

/**
 * An error occurred during query building
 *
 * When this exception is thrown, it means that the query could not be built, so it's not executed.
 * This means that the error results from invalid code, and can only be fixed by changing the code.
 */
final class QueryBuildingException extends LogicException implements DatabaseExceptionInterface
{
    public function __construct(
        public readonly string|UnitEnum $connection,
        public readonly string $query,
        string $message,
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
