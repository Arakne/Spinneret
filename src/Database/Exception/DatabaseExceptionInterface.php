<?php

namespace Arakne\Spinneret\Database\Exception;

use Throwable;
use UnitEnum;

/**
 * Base type for database exceptions
 */
interface DatabaseExceptionInterface extends Throwable
{
    /**
     * Get the connection name where the exception occurred
     */
    public function connection(): string|UnitEnum;
}
