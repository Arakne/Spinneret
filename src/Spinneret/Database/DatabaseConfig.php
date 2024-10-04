<?php

namespace Arakne\Spinneret\Database;

use UnitEnum;

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
     * @param ConnectionConfig[] $connections
     */
    public function __construct(
        array $connections = [],

        /**
         * Enable or disable the migration system
         *
         * Note: this parameter is not resolved dynamically, so do not use an environment variable
         */
        public bool $useMigration = true,

        /**
         * The connection to use for storing the migration status
         *
         * If null, the migration status will not be stored, so migrations will be executed every time
         * The connection must be defined in the connections array
         *
         *  Note: this parameter is not resolved dynamically, so do not use an environment variable
         */
        public string|UnitEnum|null $migrationConnection = null,
    ) {
        $connectionsByName = [];

        foreach ($connections as $connection) {
            $name = is_string($connection->name) ? $connection->name : $connection->name->name;
            $connectionsByName[$name] = $connection;
        }

        $this->connections = $connectionsByName;
    }
}
