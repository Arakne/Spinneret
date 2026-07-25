<?php

namespace Arakne\Spinneret\Database\Schema;

/**
 * Type for inspecting the database schema.
 */
interface DatabaseSchemaInterface
{
    /**
     * Check if an index exists on a table.
     *
     * @param string $table The table name
     * @param string $index The index name
     *
     * @return bool
     */
    public function hasIndex(string $table, string $index): bool;

    /**
     * Check if a table exists.
     *
     * @param string $table The table name
     *
     * @return bool
     */
    public function hasTable(string $table): bool;

    /**
     * Check if a column exists in a table.
     *
     * @param string $table The table name
     * @param string $column The column name
     *
     * @return bool
     */
    public function hasColumn(string $table, string $column): bool;

    /**
     * Does the current database use SQLite?
     */
    public function isSqlite(): bool;

    /**
     * Does the current database use MySQL?
     */
    public function isMySql(): bool;
}
