<?php

namespace Arakne\Spinneret\Database\Schema;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;
use Arakne\Spinneret\Database\Exception\TableNotFoundException;
use Override;

use function addcslashes;
use function str_replace;
use function var_dump;

final readonly class MySqlSchema implements DatabaseSchemaInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {
    }

    #[Override]
    public function hasIndex(string $table, string $index): bool
    {
        try {
            $stmt = $this->connection->prepare('SHOW INDEX FROM {table} WHERE key_name = ?');

            $stmt->setExpression('table', self::quoteTable($table));
            $stmt->pushString($index);

            return $stmt->execute()->fetchColumn(0) !== false;
        } catch (TableNotFoundException) {
            return false;
        }
    }

    #[Override]
    public function hasTable(string $table): bool
    {
        $stmt = $this->connection->prepare('SHOW TABLES LIKE {table}'); // MySQL do not support parameterized table names
        $stmt->setExpression('table', $this->connection->quote($table));

        return $stmt->execute()->fetchColumn(0) !== false;
    }

    #[Override]
    public function hasColumn(string $table, string $column): bool
    {
        try {
            $stmt = $this->connection->prepare('SHOW COLUMNS FROM {table} WHERE field = ?');

            $stmt->setExpression('table', self::quoteTable($table));
            $stmt->pushString($column);

            return $stmt->execute()->fetchColumn(0) !== false;
        } catch (TableNotFoundException) {
            return false;
        }
    }

    #[Override]
    public function isSqlite(): bool
    {
        return false;
    }

    #[Override]
    public function isMySql(): bool
    {
        return true;
    }

    private static function quoteTable(string $table): string
    {
        return '`' . str_replace('`', '``', $table) . '`';
    }
}
