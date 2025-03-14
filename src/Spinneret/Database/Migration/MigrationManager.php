<?php

namespace Arakne\Spinneret\Database\Migration;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\Repository\MigrationRepositoryInterface;
use Closure;
use Psr\Log\LoggerInterface;

use function array_flip;
use function array_key_exists;
use function iterator_to_array;
use function microtime;
use function round;
use function usort;
use function version_compare;

/**
 * Manage project migrations
 */
final readonly class MigrationManager
{
    public function __construct(
        private MigrationRepositoryInterface $repository,
        private DatabaseConnectionManagerInterface $connectionManager,

        /**
         * Closure that return all migrations
         * The closure must return a new iterable on each call
         *
         * @var Closure():iterable<array-key, MigrationInterface>
         */
        private Closure $migrationsResolver,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * List all migrations (applied or not)
     *
     * @return array<MigrationStatus>
     */
    public function list(): array
    {
        $statuses = [];

        foreach ($this->resolve() as $migration) {
            $statuses[] = new MigrationStatus($migration, $this->repository->isApplied($migration));
        }

        return $statuses;
    }

    /**
     * Apply all pending migrations
     *
     * @param (Closure(string, bool=):void)|null $output A callback to output messages. Take the message as first argument and a boolean to indicate if the new line should be added.
     *
     * @return int The number of applied migrations
     */
    public function up(?Closure $output = null): int
    {
        $output ??= function (string $message, bool $newLine = false): void {};
        $count = 0;
        $this->repository->initialize();

        foreach ($this->resolve() as $migration) {
            if ($this->repository->isApplied($migration)) {
                continue;
            }

            $start = microtime(true);
            $output("Applying migration {$migration->name()}...", true);

            $migration->up($this->connectionManager, $output);
            $this->repository->markAsApplied($migration);

            $time = round((microtime(true) - $start) * 1000.0, 2);
            $output("Migration {$migration->name()} applied in {$time}ms", true);
            $this->logger?->info('Migration {{ migration }} applied in {{ time }} ms', [
                'migration' => $migration->name(),
                'time' => $time,
                'migration_class' => $migration::class,
            ]);
            ++$count;
        }

        return $count;
    }

    /**
     * Rollback to a specific version
     *
     * This method will call {@see MigrationInterface::down()} on each applied migrations from the latest to the newest
     * until the version is reached.
     *
     * @param string $version The version to rollback to. Should be compatible with {@see version_compare}.
     * @param bool $force If true, will rollback all migrations until the given version even if they are not marked as applied.
     * @param (Closure(string, bool=):void)|null $output A callback to output messages. Take the message as first argument and a boolean to indicate if the new line should be added.
     *
     * @return int Number of rolled back migrations
     */
    public function rollback(string $version, bool $force = false, ?Closure $output = null): int
    {
        $output ??= function (string $message, bool $newLine = false): void {};
        $count = 0;

        $migrations = $this->resolve(version: $version, oldersBefore: false);

        foreach ($migrations as $migration) {
            if (!$force && !$this->repository->isApplied($migration)) {
                continue;
            }

            $start = microtime(true);
            $output("Rolling back migration {$migration->name()}...", true);

            $migration->down($this->connectionManager, $output);
            $this->repository->remove($migration);

            $time = round((microtime(true) - $start) * 1000.0, 2);
            $output("Migration {$migration->name()} rolled back in {$time}ms", true);
            $this->logger?->info('Migration {{ migration }} rolled back in {{ time }} ms', [
                'migration' => $migration->name(),
                'time' => $time,
                'migration_class' => $migration::class,
            ]);
            ++$count;
        }

        return $count;
    }

    /**
     * Rollback a list of migrations
     *
     * This method will call {@see MigrationInterface::down()} on each migrations
     *
     * @param list<string> $migrations The migrations names to rollback
     * @param bool $force If true, will rollback all migrations even if they are not marked as applied.
     * @param (Closure(string, bool=):void)|null $output A callback to output messages. Take the message as first argument and a boolean to indicate if the new line should be added.
     *
     * @return int Number of rolled back migrations
     */
    public function down(array $migrations, bool $force = false, ?Closure $output = null): int
    {
        $output ??= function (string $message, bool $newLine = false): void {};
        $count = 0;

        $migrationsInstances = $this->resolve(names: $migrations, oldersBefore: false);

        foreach ($migrationsInstances as $migration) {
            if (!$force && !$this->repository->isApplied($migration)) {
                continue;
            }

            $start = microtime(true);
            $output("Rolling back migration {$migration->name()}...", true);

            $migration->down($this->connectionManager, $output);
            $this->repository->remove($migration);

            $time = round((microtime(true) - $start) * 1000.0, 2);
            $output("Migration {$migration->name()} rolled back in {$time}ms", true);
            $this->logger?->info('Migration {{ migration }} rolled back in {{ time }} ms', [
                'migration' => $migration->name(),
                'time' => $time,
                'migration_class' => $migration::class,
            ]);
            ++$count;
        }

        return $count;
    }

    /**
     * Resolve all migrations that match the given criteria
     *
     * @param string|null $version Match the version of the migration. If $oldersBefore is true, will return all migrations older until the given version included. If $oldersBefore is false, will return all migrations newer until the given version excluded.
     * @param list<string> $names Match the names of the migrations. If empty, all migrations will be returned.
     * @param bool $oldersBefore If true migrations will be sorted from the oldest to the newest, otherwise from the newest to the oldest.
     *
     * @return list<MigrationInterface>
     */
    private function resolve(?string $version = null, array $names = [], bool $oldersBefore = true): array
    {
        /**
         * @var array<MigrationInterface> $migrations
         * @psalm-suppress InvalidArgument
         */
        $migrations = iterator_to_array(($this->migrationsResolver)());
        $names = array_flip($names);

        if ($oldersBefore) {
            usort($migrations, fn (MigrationInterface $a, MigrationInterface $b) => $a->date() <=> $b->date());
        } else {
            usort($migrations, fn (MigrationInterface $a, MigrationInterface $b) => $b->date() <=> $a->date());
        }

        $filtered = [];

        foreach ($migrations as $migration) {
            if (!$migration->supports($this->connectionManager)) {
                continue;
            }

            if ($version !== null && version_compare($migration->version(), $version, $oldersBefore ? '>' : '<=')) {
                break;
            }

            if ($names && !array_key_exists($migration->name(), $names)) {
                continue;
            }

            $filtered[] = $migration;
        }

        return $filtered;
    }

    /**
     * Get the current version of the database
     *
     * @return string|null The current version or null if no migration has been applied
     */
    public function currentVersion(): ?string
    {
        return $this->repository->lastVersion();
    }
}
