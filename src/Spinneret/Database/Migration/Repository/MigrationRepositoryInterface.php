<?php

namespace Arakne\Spinneret\Database\Migration\Repository;

use Arakne\Spinneret\Database\Migration\MigrationInterface;

/**
 * Repository for store applied migrations
 */
interface MigrationRepositoryInterface
{
    /**
     * Initialize the migration storage
     * If the storage is already initialized, this method should do nothing
     */
    public function initialize(): void;

    /**
     * Check if the given migration is already applied
     * If the migration repository is not initialized, this method should return false
     *
     * @param MigrationInterface $migration The migration to check
     *
     * @return bool True if the migration is already applied, false otherwise
     */
    public function isApplied(MigrationInterface $migration): bool;

    public function markAsApplied(MigrationInterface $migration): void;

    public function remove(MigrationInterface $migration): void;

    /**
     * @return string|null The version of the last applied migration, or null if no migration has been applied
     */
    public function lastVersion(): ?string;
}
