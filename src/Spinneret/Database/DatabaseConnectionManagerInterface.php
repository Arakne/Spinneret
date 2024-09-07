<?php

namespace Arakne\Spinneret\Database;

use InvalidArgumentException;
use UnitEnum;

/**
 * Base type for managing database connections
 */
interface DatabaseConnectionManagerInterface
{
    /**
     * Get a database connection by name
     * The connection must be defined in the configuration.
     *
     * @param string|UnitEnum $name The name of the connection. if the name is an enum item, the enum item name will be used as the connection name.
     *
     * @return DatabaseConnectionInterface The connection object
     * @throws InvalidArgumentException If the connection name is unknown
     */
    public function get(string|UnitEnum $name): DatabaseConnectionInterface;
}
