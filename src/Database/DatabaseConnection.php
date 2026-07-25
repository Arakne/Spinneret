<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Arakne\Spinneret\Database\Schema\DatabaseSchemaInterface;
use LogicException;
use Override;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use UnitEnum;

use function assert;
use function sprintf;
use function strstr;

/**
 * Simple implementation of a database connection
 * Wrap an internal PDO connection
 *
 * @implements DatabaseConnectionInterface<PDO>
 */
final class DatabaseConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;
    private ?string $driver = null;

    public function __construct(
        private readonly ConnectionConfig $config,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function name(): string|UnitEnum
    {
        return $this->config->name;
    }

    #[Override]
    public function driver(): string
    {
        return $this->driver ??= (strstr($this->config->dsn, ':', true) ?: throw new LogicException(sprintf('Missing driver on DSN %s', $this->config->dsn)));
    }

    #[Override]
    public function query(string $query): QueryResult
    {
        $this->logger?->debug('Execute read query "{query}"', ['query' => $query]);
        $retry = $this->config->autoReconnect;

        for (;;) {
            try {
                // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
                $stmt = @$this->internalConnection()->query($query);
                assert($stmt !== false);

                return new QueryResult($stmt);
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
        $this->logger?->debug('Execute write query "{query}"', ['query' => $query]);
        $retry = $this->config->autoReconnect;

        for (;;) {
            try {
                // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
                /** @var int - false cannot be returned due to PDO config */
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
        return new QueryStatement($this, $this->config->autoReconnect, $query, logger: $this->logger);
    }

    #[Override]
    public function quote(string $value): string
    {
        return $this->internalConnection()->quote($value);
    }

    #[Override]
    public function internalConnection(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $this->logger?->debug('Connect to database {dsn}', ['dsn' => $this->config->dsn]);

        try {
            return $this->connection = new PDO($this->config->dsn, $this->config->username, $this->config->password, $this->config->options + [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromDatabaseConnection($e, $this->name());
        }
    }

    #[Override]
    public function reconnect(): void
    {
        $this->logger?->debug('Reconnect to database {dsn}', ['dsn' => $this->config->dsn]);

        $this->connection = null;
        $this->internalConnection();
    }

    #[Override]
    public function schema(): DatabaseSchemaInterface
    {
        return match ($this->driver()) {
            'mysql' => new Schema\MySqlSchema($this),
            'sqlite' => new Schema\SqliteSchema($this),
            default => throw new LogicException('Unsupported driver: ' . $this->driver()),
        };
    }
}
