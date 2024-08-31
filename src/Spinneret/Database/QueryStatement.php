<?php

namespace Arakne\Spinneret\Database;

use PDO;
use PDOStatement;

use function count;
use function is_array;
use function preg_replace_callback;
use function str_repeat;
use function strpos;
use function strtr;

// @todo "allow expression" flag
// @todo has array flag
// @todo allow enum for native types
final class QueryStatement
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

    public function pushInt(int $value): self
    {
        $this->parameters[] = [$value, PDO::PARAM_INT];

        return $this;
    }

    public function pushString(string $value): self
    {
        $this->parameters[] = [$value, PDO::PARAM_STR];

        return $this;
    }

    public function pushBool(bool $value): self
    {
        $this->parameters[] = [$value, PDO::PARAM_BOOL];

        return $this;
    }

    public function pushNull(): self
    {
        $this->parameters[] = [null, PDO::PARAM_NULL];

        return $this;
    }

    /**
     * Push a new argument as array of integers.
     *
     * The spread placeholder `...?` must be present in the query.
     * Keys of the array are ignored.
     *
     * @param array<int> $values
     *
     * @return $this
     */
    public function pushArrayOfInt(array $values): self
    {
        $this->parameters[] = [$values, PDO::PARAM_INT];

        return $this;
    }

    // @todo separator argument ?
    public function pushExpression(string $placeholder, string $expression): self
    {
        if (!isset($this->expressions[$placeholder])) {
            $this->expressions[$placeholder] = '';
        }

        $this->expressions[$placeholder] .= $expression;

        return $this;
    }

    public function execute(): QueryResult
    {
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
        foreach ($this->parameters as [$value, $type]) {
            if (is_array($value)) {
                // @todo handle empty array. Should we inject null ?
                // @todo handle spread not found
                $spreadPos = strpos($query, '...?', $spreadPos);

                $placeholders = '?';

                if (count($value) > 1) {
                    $placeholders .= str_repeat(', ?', count($value) - 1);
                }

                $query = substr_replace($query, $placeholders, $spreadPos, 4);

                foreach ($value as $v) {
                    $parameters[++$parameterNumber] = [$v, $type];
                }

                continue;
            }

            $parameters[++$parameterNumber] = [$value, $type];
        }

        $this->statement = $stmt = $this->connection->internalConnection()->prepare($query);

        foreach ($parameters as $position => [$value, $type]) {
            $stmt->bindValue($position, $value, $type);
        }

        // @todo handle return false
        $stmt->execute();

        return new QueryResult($stmt);
    }
}
