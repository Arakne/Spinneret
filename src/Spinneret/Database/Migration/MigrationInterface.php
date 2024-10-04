<?php

namespace Arakne\Spinneret\Database\Migration;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Closure;
use DateTimeImmutable;

/**
 * Base type for defining a migration
 */
interface MigrationInterface
{
    /**
     * Check if the current connection configuration is supported by the migration
     * This is used to discriminate between multiple database drivers (like MySQL, PostgreSQL, SQLite, etc.)
     */
    public function supports(DatabaseConnectionManagerInterface $connections): bool;

    /**
     * Apply the migration
     *
     * It's recommended to silently ignore the migration if it's already applied
     *
     * @param (Closure(string, bool=):void)|null $output A callback to output messages. Take the message as first argument and a boolean to indicate if the new line should be added.
     */
    public function up(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void;

    /**
     * Rollback the migration
     *
     * @param (Closure(string, bool=):void)|null $output A callback to output messages. Take the message as first argument and a boolean to indicate if the new line should be added.
     */
    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void;

    /**
     * The migration name
     *
     * Should be unique across all migrations on the same database engine
     * The name must not change after the migration is created
     */
    public function name(): string;

    /**
     * The date of the creation of the migration
     * Will be used to sort migrations
     */
    public function date(): DateTimeImmutable;

    /**
     * The version string of the application that create the migration
     *
     * This version is use if you need to rollback to a certain version.
     * It should be compatible with {@see version_compare}.
     */
    public function version(): string;
}
