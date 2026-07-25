<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Logger\ContextLogger;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use UnitEnum;

use function is_string;

/**
 * Base implementation for managing database connections
 */
final class DatabaseConnectionManager implements DatabaseConnectionManagerInterface
{
    /**
     * @var array<string, DatabaseConnection>
     */
    private array $connections = [];

    public function __construct(
        private readonly DatabaseConfig $config,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function get(string|UnitEnum $name): DatabaseConnection
    {
        $name = is_string($name) ? $name : $name->name;
        $connection = $this->connections[$name] ?? null;

        if ($connection) {
            return $connection;
        }

        $config = $this->config->connections[$name] ?? throw new InvalidArgumentException("Unknown connection: $name");
        $logger = $this->logger !== null ? new ContextLogger($this->logger, "[$name]", ['database' => $name]) : null;

        return $this->connections[$name] = new DatabaseConnection($config, $logger);
    }
}
