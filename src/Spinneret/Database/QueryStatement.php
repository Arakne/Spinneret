<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Arakne\Spinneret\Database\Exception\QueryBuildingException;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;

use function array_column;
use function count;
use function is_array;
use function preg_replace_callback;
use function str_repeat;
use function strpos;
use function var_dump;

/**
 * Implementation of QueryStatementInterface for PDOStatement
 */
final class QueryStatement implements QueryStatementInterface
{
    private ?PDOStatement $statement = null;

    /**
     * @var list<array{0: mixed, 1: PDO::PARAM_*}>
     */
    private array $parameters = [];

    /**
     * @var array<string, string>
     */
    private array $expressions = [];

    /**
     * Check if the query contains an array parameter
     */
    private bool $hasArray = false;

    public function __construct(
        private readonly DatabaseConnection $connection,

        /**
         * Enable automatic reconnection if the connection is lost
         */
        private readonly bool $autoReconnect,

        /**
         * The SQL query
         * Should not be user provided
         *
         * The query may contain placeholders `?` for parameters, array spread `...?` for array parameters, and expressions `{placeholder}`.
         */
        private readonly string $query,

        /**
         * Does the query contain dynamic expressions ?
         *
         * If true, expression will be evaluated at each execution.
         * If false, expressions will not be evaluated.
         * If null, the query will be scanned for expressions.
         */
        private ?bool $dynamic = null,
        private ?LoggerInterface $logger = null,
    ) {}

    #[Override]
    public function pushInt(int $value): static
    {
        $this->parameters[] = [$value, PDO::PARAM_INT];

        return $this;
    }

    #[Override]
    public function pushString(string $value): static
    {
        $this->parameters[] = [$value, PDO::PARAM_STR];

        return $this;
    }

    #[Override]
    public function pushBool(bool $value): static
    {
        $this->parameters[] = [$value, PDO::PARAM_BOOL];

        return $this;
    }

    #[Override]
    public function pushNull(): static
    {
        $this->parameters[] = [null, PDO::PARAM_NULL];

        return $this;
    }

    #[Override]
    public function pushArrayOfInt(array $values): static
    {
        $this->pushArray($values, PDO::PARAM_INT);

        return $this;
    }

    #[Override]
    public function pushArrayOfString(array $values): static
    {
        $this->pushArray($values, PDO::PARAM_STR);

        return $this;
    }

    #[Override]
    public function setInt(int $index, int $value): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->parameters[$index] = [$value, PDO::PARAM_INT];

        return $this;
    }

    #[Override]
    public function setString(int $index, string $value): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->parameters[$index] = [$value, PDO::PARAM_STR];

        return $this;
    }

    #[Override] public function setBool(int $index, bool $value): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->parameters[$index] = [$value, PDO::PARAM_BOOL];

        return $this;
    }

    #[Override]
    public function setNull(int $index): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->parameters[$index] = [null, PDO::PARAM_NULL];

        return $this;
    }

    #[Override]
    public function setArrayOfInt(int $index, array $values): static
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->setArray($index, $values, PDO::PARAM_INT);

        return $this;
    }

    #[Override]
    public function setArrayOfString(int $index, array $values): static
    {
        $this->setArray($index, $values, PDO::PARAM_STR);

        return $this;
    }

    #[Override]
    public function setExpression(string $placeholder, string $expression): static
    {
        $this->expressions[$placeholder] = $expression;
        $this->statement = null; // Reset statement to force rebuild

        return $this;
    }

    #[Override]
    public function pushExpression(string $placeholder, string $expression, string $separator = ' '): static
    {
        $current = $this->expressions[$placeholder] ?? '';

        if ($current !== '') {
            $current .= $separator;
        }

        $current .= $expression;

        $this->expressions[$placeholder] = $current;
        $this->statement = null; // Reset statement to force rebuild

        return $this;
    }

    #[Override]
    public function reset(): void
    {
        // The statement should be rebuilt in case of dynamic expressions
        if ($this->hasArray || $this->expressions) {
            $this->statement = null;
        }

        $this->parameters = [];
        $this->expressions = [];
    }

    #[Override]
    public function execute(): QueryResult
    {
        return new QueryResult($this->executeStatement());
    }

    #[Override]
    public function executeUpdate(): int
    {
        /** @var non-negative-int */
        return $this->executeStatement()->rowCount();
    }

    #[Override]
    public function executeWithGeneratedKey(): string
    {
        $this->executeStatement();

        return $this->connection->internalConnection()->lastInsertId();
    }

    /**
     * @param array $values
     * @param PDO::PARAM_* $type
     *
     * @return void
     */
    private function pushArray(array $values, int $type): void
    {
        $this->parameters[] = [$values, $type];
        $this->hasArray = true;
        $this->statement = null; // Reset statement to force rebuild
    }

    /**
     * @param int $index
     * @param array $values
     * @param PDO::PARAM_* $type
     *
     * @return void
     */
    private function setArray(int $index, array $values, int $type): void
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->parameters[$index] = [$values, $type];
        $this->hasArray = true;
        $this->statement = null; // Reset statement to force rebuild
    }

    private function executeStatement(): PDOStatement
    {
        $retry = $this->autoReconnect;

        for (;;) {
            try {
                $conn = $this->connection->internalConnection();

                if (($stmt = $this->statement) === null) {
                    return $this->statement = $this->buildStatement($conn);
                }

                $this->executeWithNewParameters($stmt);
                return $stmt;
            } catch (DatabaseConnectionLostException $e) {
                $this->statement = null;

                if (!$retry) {
                    throw $e;
                }

                $this->connection->reconnect();
                $retry = false;
            }
        }
    }

    private function executeWithNewParameters(PDOStatement $statement): void
    {
        if (!$this->hasArray) {
            // Fast path for query with atomic parameters
            $parameters = $this->parameters;
        } else {
            // Expand array parameters
            $parameters = [];

            /**
             * @var int $position
             * @var mixed $value
             * @var PDO::PARAM_* $type
             */
            foreach ($this->parameters as [$value, $type]) {
                if (!is_array($value)) {
                    $parameters[] = [$value, $type];
                    continue;
                }

                /** @var mixed $v */
                foreach ($value as $v) {
                    $parameters[] = [$v, $type];
                }
            }
        }

        $this->logger?->debug('Execute reused prepared query "{query}"', ['query' => $statement->queryString, 'parameters' => $parameters]);

        /**
         * @var int $position
         * @var mixed $value
         * @var PDO::PARAM_* $type
         */
        foreach ($parameters as $position => [$value, $type]) {
            $statement->bindValue($position + 1, $value, $type);
        }

        try {
            // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
            @$statement->execute();
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromQueryExecution($e, $this->connection->name(), $statement->queryString, array_column($parameters, 0));
        }
    }

    private function buildStatement(PDO $connection): PDOStatement
    {
        $query = $this->applyExpressions($this->query);
        [$query, $parameters] = $this->spreadArrayParameters($query, $this->parameters);

        $this->logger?->debug('Execute prepared query "{query}"', ['query' => $query, 'parameters' => $parameters]);

        try {
            // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
            $this->statement = $stmt = @$connection->prepare($query);
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromQueryExecution($e, $this->connection->name(), $query, array_column($parameters, 0));
        }

        /**
         * @var int $position
         * @var mixed $value
         * @var PDO::PARAM_* $type
         */
        foreach ($parameters as $position => [$value, $type]) {
            $stmt->bindValue($position + 1, $value, $type);
        }

        try {
            // Ignore warning "Packets out of order. Expected 1 received 0. Packet size=145"
            @$stmt->execute();
        } catch (PDOException $e) {
            throw DatabaseExceptionFactory::fromQueryExecution($e, $this->connection->name(), $query, array_column($parameters, 0));
        }

        return $stmt;
    }

    /**
     * Replace expressions placeholders by their values
     */
    private function applyExpressions(string $query): string
    {
        if (($this->dynamic ??= $this->hasExpression($query)) === false) {
            return $query;
        }

        return preg_replace_callback(
            '/\{([a-z0-9_.-]+)}/iu',
            fn ($matches) => $this->expressions[$matches[1]] ?? '',
            $query
        );
    }

    /**
     * Resolve spread array parameters and return the new query and flattened parameters
     *
     * @param string $query
     * @param list<array{0: mixed, 1: PDO::PARAM_*}> $parameters
     *
     * @return list{string, list<array{0: mixed, 1: PDO::PARAM_*}>}
     */
    private function spreadArrayParameters(string $query, array $parameters): array
    {
        if (!$this->hasArray) {
            return [$query, $parameters];
        }

        $parameters = [];
        $spreadPos = 0;

        /** @var mixed $value */
        foreach ($this->parameters as [$value, $type]) {
            if (!is_array($value)) {
                $parameters[] = [$value, $type];
                continue;
            }

            $spreadPos = strpos($query, '...?', $spreadPos);

            if ($spreadPos === false) {
                throw new QueryBuildingException(
                    $this->connection->name(),
                    $query,
                    'The spread placeholder `...?` must be present in the query when using an array parameter.'
                );
            }

            $placeholders = '?';

            if (($c = count($value)) > 1) {
                $placeholders .= str_repeat(', ?', $c - 1);
            }

            $query = substr_replace($query, $placeholders, $spreadPos, 4);

            if (empty($value)) {
                // if the array is empty, we inject a null value
                $parameters[] = [null, PDO::PARAM_NULL];
            } else {
                /** @var mixed $v */
                foreach ($value as $v) {
                    $parameters[] = [$v, $type];
                }
            }
        }

        return [$query, $parameters];
    }

    /**
     * Check if the current query has expressions
     */
    private function hasExpression(string $query): bool
    {
        if ($this->expressions) {
            return true;
        }

        if (($open = strpos($query, '{')) === false) {
            return false;
        }

        return strpos($query, '}', $open) !== false;
    }
}
