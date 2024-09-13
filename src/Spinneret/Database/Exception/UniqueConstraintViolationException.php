<?php

namespace Arakne\Spinneret\Database\Exception;

use Throwable;
use UnitEnum;

class UniqueConstraintViolationException extends QueryExecutionException
{
    public function __construct(
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
