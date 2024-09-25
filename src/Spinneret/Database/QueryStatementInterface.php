<?php

namespace Arakne\Spinneret\Database;

use Arakne\Spinneret\Database\Exception\DatabaseExceptionInterface;

/**
 * Interface for parameterized query statements.
 */
interface QueryStatementInterface
{
    /**
     * Push a new parameter as integer.
     * The next placeholder `?` in the query will be replaced by the value.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE id = ?');
     * $query->pushInt(42);
     * $result = $query->execute();
     * ```
     *
     * @param int $value The value to push
     * @return $this
     */
    public function pushInt(int $value): static;

    /**
     * Push a new parameter as string.
     * The next placeholder `?` in the query will be replaced by the value.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE name = ?');
     * $query->pushString('John');
     * $result = $query->execute();
     * ```
     *
     * @param string $value The value to push
     * @return $this
     */
    public function pushString(string $value): static;

    /**
     * Push a new parameter as boolean.
     * The next placeholder `?` in the query will be replaced by the value.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE active = ?');
     * $query->pushBool(true);
     * $result = $query->execute();
     * ```
     *
     * @param bool $value The value to push
     * @return $this
     */
    public function pushBool(bool $value): static;

    /**
     * Push a new parameter as null.
     * The next placeholder `?` in the query will be replaced by the value.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('INSERT INTO table (id, name) VALUES (?, ?)');
     * $query->pushNull()->pushString('John');
     * $id = $query->executeGenerateKey();
     * ```
     *
     * @return $this
     */
    public function pushNull(): static;

    /**
     * Push a new argument as array of integers.
     *
     * The spread placeholder `...?` must be present in the query.
     * Keys of the array are ignored.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE id IN (...?)');
     * $query->pushArrayOfInt([1, 2, 3]);
     * $result = $query->execute();
     * ```
     *
     * @param array<int> $values
     *
     * @return $this
     */
    public function pushArrayOfInt(array $values): static;

    /**
     * Push a new argument as array of strings.
     *
     * The spread placeholder `...?` must be present in the query.
     * Keys of the array are ignored.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE name IN (...?)');
     * $query->pushArrayOfString(['John', 'Jane']);
     * $result = $query->execute();
     * ```
     *
     * @param array<string> $values
     *
     * @return $this
     */
    public function pushArrayOfString(array $values): static;

    /**
     * Replace the expression placeholder with the given expression.
     *
     * The placeholder must be present in the query in form of `{placeholder}`.
     * The placeholder must be composed of letters, numbers and following characters: `_`, `-`, `.`.
     *
     * If the placeholder is not present in the query, the expression will be ignored.
     * If the placeholder is present, but no expression is set, the placeholder will be replaced with an empty string.
     *
     * The expression can contain placeholders itself. In this case, values must be set just after the expression is set.
     *
     * Note: Be careful on reusing the statement if an expression has parameters: the parameters index depends on the order of the expressions.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE {filters}');
     * $query->setExpression('filters', 'active = ?');
     * $query->pushBool(true);
     * $result = $query->execute();
     * ```
     *
     * @param string $placeholder The placeholder to replace without '{}'
     * @param string $expression The expression to set. This value must not be user-provided.
     *
     * @return $this
     *
     * @see QueryStatementInterface::pushExpression() For appending expressions instead of replacing them
     */
    public function setExpression(string $placeholder, string $expression): static;

    /**
     * Append the given expression to the placeholder, and separate them with the given separator if the placeholder is already set.
     *
     * The placeholder must be present in the query in form of `{placeholder}`.
     * The placeholder must be composed of letters, numbers and following characters: `_`, `-`, `.`.
     *
     * If the placeholder is not present in the query, the expression will be ignored.
     * If the placeholder is present, but no expression is set, the placeholder will be replaced with an empty string.
     *
     * The expression can contain placeholders itself. In this case, values must be set just after the expression is set.
     *
     * Note: Be careful on reusing the statement if an expression has parameters: the parameters index depends on the order of the expressions.
     *
     * Usage:
     * ```php
     * $query = $connection->prepare('SELECT * FROM table WHERE {filters}');
     * $query->pushExpression('filters', 'active = ?', ' AND ');
     * $query->pushBool(true);
     * $query->pushExpression('filters', 'name = ?', ' AND ');
     * $query->pushString('John');
     * $result = $query->execute();
     * ```
     *
     * @param string $placeholder The placeholder to replace without '{}'
     * @param string $expression The expression to set. This value must not be user-provided.
     * @param string $separator The separator to use between the expressions.
     *
     * @return $this
     *
     * @see QueryStatementInterface::setExpression() For replacing expressions instead of appending them
     */
    public function pushExpression(string $placeholder, string $expression, string $separator = ' '): static;

    /**
     * Execute the query and fetch the result.
     *
     * @return QueryResultInterface
     * @throws DatabaseExceptionInterface
     */
    public function execute(): QueryResultInterface;

    /**
     * Execute an update query and return the number of affected rows.
     *
     * @return non-negative-int The number of affected rows
     * @throws DatabaseExceptionInterface
     */
    public function executeUpdate(): int;

    /**
     * Execute an insert query and return the generated key.
     *
     * Note: if the query does not generate a key, the behavior is undefined.
     *
     * @return string The generated key
     * @throws DatabaseExceptionInterface
     */
    public function executeWithGeneratedKey(): string;
}
