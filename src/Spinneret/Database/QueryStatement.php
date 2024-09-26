<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseConnectionLostException;
use Arakne\Spinneret\Database\Exception\DatabaseExceptionFactory;
use Arakne\Spinneret\Database\Exception\QueryBuildingException;
use Override;
use PDO;
use PDOException;
use PDOStatement;

use function array_column;
use function count;
use function is_array;
use function preg_replace_callback;
use function str_repeat;
use function strpos;

/**
 * Implementation of QueryStatementInterface for PDOStatement
 *
 * @todo method for reusing the same statement with different parameters
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
    ) {
    }

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
        $this->parameters[] = [$values, PDO::PARAM_INT];
        $this->hasArray = true;

        return $this;
    }

    #[Override]
    public function pushArrayOfString(array $values): static
    {
        $this->parameters[] = [$values, PDO::PARAM_STR];
        $this->hasArray = true;

        return $this;
    }

    #[Override]
    public function setExpression(string $placeholder, string $expression): static
    {
        $this->expressions[$placeholder] = $expression;

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

        return $this;
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

    private function executeStatement(): PDOStatement
    {
        $retry = $this->autoReconnect;

        for (;;) {
            try {
                $conn = $this->connection->internalConnection();
                return $this->statement = $this->buildStatement($conn);
            } catch (DatabaseConnectionLostException $e) {
                if (!$retry) {
                    throw $e;
                }

                $this->connection->reconnect();
                $retry = false;
            }
        }
    }

    private function buildStatement(PDO $connection): PDOStatement
    {
        $query = $this->applyExpressions($this->query);
        [$query, $parameters] = $this->spreadArrayParameters($query, $this->parameters);

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
    public function spreadArrayParameters(string $query, array $parameters): array
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
