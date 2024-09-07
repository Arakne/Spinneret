<?php

namespace Arakne\Spinneret\Database;

use function is_string;

/**
 * Configuration for the database modules
 */
final readonly class DatabaseConfig
{
    /**
     * @var array<string, ConnectionConfig>
     */
    public array $connections;

    /**
     * @param ConnectionConfig ...$connections
     * @no-named-arguments
     */
    public function __construct(ConnectionConfig ...$connections)
    {
        $connectionsByName = [];

        foreach ($connections as $connection) {
            $name = is_string($connection->name) ? $connection->name : $connection->name->name;
            $connectionsByName[$name] = $connection;
        }

        $this->connections = $connectionsByName;
    }
}
