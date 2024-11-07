<?php

namespace Arakne\Spinneret\Database\Schema;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;
use Override;

use function str_replace;

final readonly class SqliteSchema implements DatabaseSchemaInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {
    }

    #[Override]
    public function hasIndex(string $table, string $index): bool
    {
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM sqlite_master WHERE type = ? AND name = ? AND tbl_name = ?');

        $stmt->pushString('index');
        $stmt->pushString($index);
        $stmt->pushString($table);

        return (int) $stmt->execute()->fetchColumn(0) > 0;
    }

    #[Override]
    public function hasTable(string $table): bool
    {
        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM sqlite_master WHERE type = ? AND name = ?');

        $stmt->pushString('table');
        $stmt->pushString($table);

        return (int) $stmt->execute()->fetchColumn(0) > 0;
    }

    #[Override]
    public function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->connection
            ->prepare('PRAGMA table_info({table});')
            ->pushExpression('table', self::quoteTable($table))
        ;

        $result = $stmt->execute();

        /** @psalm-suppress MixedAssignment */
        while (($fetched = $result->fetchColumn(1)) !== false) {
            if ($fetched === $column) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function isSqlite(): bool
    {
        return true;
    }

    #[Override]
    public function isMySql(): bool
    {
        return false;
    }

    private static function quoteTable(string $table): string
    {
        return '`' . str_replace('`', '``', $table) . '`';
    }
}
