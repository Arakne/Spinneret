<?php

namespace Arakne\Spinneret\Database;

use LogicException;
use Override;
use PDO;
use PDOStatement;

use function count;
use function is_array;
use function preg_replace_callback;
use function str_repeat;
use function strpos;

// @todo "allow expression" flag
// @todo has array flag

/**
 * Implementation of QueryStatementInterface for PDOStatement
 *
 * @todo method for reusing the same statement with different parameters
 */
final class QueryStatement implements QueryStatementInterface
{
    private ?PDOStatement $statement = null;
    private bool $built = false;

    /**
     * @var list<array{0: mixed, 1: PDO::PARAM_*}>
     */
    private array $parameters = [];

    /**
     * @var array<string, string>
     */
    private array $expressions = [];

    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly string $query,
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

        return $this;
    }

    #[Override]
    public function pushArrayOfString(array $values): static
    {
        $this->parameters[] = [$values, PDO::PARAM_STR];

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
        $conn = $this->connection->internalConnection();
        $this->statement = $stmt = $this->buildStatement($conn);

        return new QueryResult($stmt);
    }

    #[Override]
    public function executeUpdate(): int
    {
        $conn = $this->connection->internalConnection();
        $this->statement = $this->buildStatement($conn);

        /** @var non-negative-int */
        return $this->statement->rowCount();
    }

    #[Override]
    public function executeWithGeneratedKey(): string
    {
        $conn = $this->connection->internalConnection();
        $this->statement = $this->buildStatement($conn);

        return $conn->lastInsertId();
    }

    private function buildStatement(PDO $connection): PDOStatement
    {
        // @todo refactor
        // @todo Execute expressions are enabled
        $query = preg_replace_callback(
            '/\{([a-z0-9_.-]+)}/iu',
            fn ($matches) => $this->expressions[$matches[1]] ?? '',
            $this->query
        );

        $parameters = [];
        $parameterNumber = 0;
        $spreadPos = 0;

        // @todo Execute only if an array expression is found
        /** @var mixed $value */
        foreach ($this->parameters as [$value, $type]) {
            if (is_array($value)) {
                // @todo handle empty array. Should we inject null ?
                // @todo handle spread not found
                $spreadPos = strpos($query, '...?', $spreadPos);

                if ($spreadPos === false) {
                    throw new LogicException('The spread placeholder `...?` must be present in the query when using an array parameter.');
                }

                $placeholders = '?';

                if (count($value) > 1) {
                    $placeholders .= str_repeat(', ?', count($value) - 1);
                }

                $query = substr_replace($query, $placeholders, $spreadPos, 4);

                /** @var mixed $v */
                foreach ($value as $v) {
                    $parameters[++$parameterNumber] = [$v, $type];
                }

                continue;
            }

            $parameters[++$parameterNumber] = [$value, $type];
        }

        $this->statement = $stmt = $connection->prepare($query);

        /**
         * @var int $position
         * @var mixed $value
         * @var PDO::PARAM_* $type
         */
        foreach ($parameters as $position => [$value, $type]) {
            $stmt->bindValue($position, $value, $type);
        }

        // @todo handle return false
        $stmt->execute();

        return $stmt;
    }
}
