<?php

namespace Arakne\Spinneret\Database\Migration\Repository;

use Arakne\Spinneret\Database\Migration\MigrationInterface;
use Override;

/**
 * Dummy implementation of a migration repository which doesn't store anything
 * When used, all migrations will be considered as not applied.
 */
final readonly class NullMigrationRepository implements MigrationRepositoryInterface
{
    #[Override]
    public function initialize(): void
    {
        // No-op
    }

    #[Override]
    public function isApplied(MigrationInterface $migration): bool
    {
        return false;
    }

    #[Override]
    public function markAsApplied(MigrationInterface $migration): void
    {
        // No-op
    }

    #[Override]
    public function remove(MigrationInterface $migration): void
    {
        // No-op
    }

    #[Override]
    public function lastVersion(): ?string
    {
        return null;
    }
}