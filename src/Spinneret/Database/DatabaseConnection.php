<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Override;
use PDO;
use PDOException;
use UnitEnum;

/**
 * Simple implementation of a database connection
 * Wrap an internal PDO connection
 *
 * @implements DatabaseConnectionInterface<PDO>
 */
final class DatabaseConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly ConnectionConfig $config,
    ) {
    }

    #[Override]
    public function name(): string|UnitEnum
    {
        return $this->config->name;
    }

    #[Override]
    public function query(string $query): QueryResult
    {
        $retry = $this->config->autoReconnect;

        for (;;) {
            try {
                // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
                return new QueryResult(@$this->internalConnection()->query($query));
            } catch (PDOException $e) {
                $e = DatabaseExceptionFactory::fromQueryExecution($e, $this->name(), $query);

                if (!$retry || !$e instanceof DatabaseConnectionLostException) {
                    throw $e;
                }

                $this->reconnect();
                $retry = false;
            }
        }
    }

    #[Override]
    public function exec(string $query): int
    {
        $retry = $this->config->autoReconnect;

        for (;;) {
            try {
                // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
                return @$this->internalConnection()->exec($query);
            } catch (PDOException $e) {
                $e = DatabaseExceptionFactory::fromQueryExecution($e, $this->name(), $query);

                if (!$retry || !$e instanceof DatabaseConnectionLostException) {
                    throw $e;
                }

                $this->reconnect();
                $retry = false;
            }
        }
    }

    #[Override]
    public function prepare(string $query): QueryStatement
    {
        return new QueryStatement($this, $this->config->autoReconnect, $query);
    }

    #[Override]
    public function internalConnection(): PDO
    {
        try {
            return $this->connection ??= new PDO($this->config->dsn, $this->config->username, $this->config->password, $this->config->options + [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromDatabaseConnection($e, $this->name());
        }
    }

    #[Override]
    public function reconnect(): void
    {
        $this->connection = null;
        $this->internalConnection();
    }
}
