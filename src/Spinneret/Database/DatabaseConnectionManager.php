<?php

namespace Arakne\Spinneret\Database;

// @todo interface
use InvalidArgumentException;
use UnitEnum;

use function is_string;

// @todo interface + wrapper
final class DatabaseConnectionManager
{
    /**
     * @var array<string, DatabaseConnection>
     */
    private array $connections = [];

    public function __construct(
        private readonly DatabaseConfig $config
    ) {
    }

    public function get(string|UnitEnum $name): DatabaseConnection
    {
        $name = is_string($name) ? $name : $name->name;
        $connection = $this->connections[$name] ?? null;

        if ($connection) {
            return $connection;
        }

        $config = $this->config->connections[$name] ?? throw new InvalidArgumentException("Unknown connection: $name");

        return $this->connections[$name] = new DatabaseConnection($config);
    }
}
