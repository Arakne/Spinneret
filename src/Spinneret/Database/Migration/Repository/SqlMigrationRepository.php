<?php

namespace Arakne\Spinneret\Database\Migration\Repository;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;
use Arakne\Spinneret\Database\Exception\TableNotFoundException;
use Arakne\Spinneret\Database\Migration\MigrationInterface;
use Arakne\Spinneret\Util\SystemClock;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Repository for store applied migrations into an SQL database
 */
final readonly class SqlMigrationRepository implements MigrationRepositoryInterface
{
    private ClockInterface $clock;

    public function __construct(
        private DatabaseConnectionInterface $db,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? SystemClock::instance();
    }

    #[Override]
    public function initialize(): void
    {
        $this->db->exec(
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS MIGRATION_STATUS (
                    MIGRATION_NAME VARCHAR(255) PRIMARY KEY,
                    MIGRATION_DATE DATETIME NOT NULL,
                    VERSION VARCHAR(32) NOT NULL,
                    APPLIED_AT DATETIME NOT NULL
                )
                SQL
        );
    }

    #[Override]
    public function isApplied(MigrationInterface $migration): bool
    {
        try {
            $count = (int) $this->db
                ->prepare('SELECT COUNT(*) FROM MIGRATION_STATUS WHERE MIGRATION_NAME = ?')
                ->pushString($migration->name())
                ->execute()
                ->fetchColumn(0)
            ;
        } catch (TableNotFoundException) {
            return false;
        }

        return $count > 0;
    }

    #[Override]
    public function markAsApplied(MigrationInterface $migration): void
    {
        $stmt = $this->db->prepare('REPLACE INTO MIGRATION_STATUS (MIGRATION_NAME, MIGRATION_DATE, VERSION, APPLIED_AT) VALUES (?, ?, ?, ?)');
        $stmt
            ->pushString($migration->name())
            ->pushString($migration->date()->format('Y-m-d H:i:s'))
            ->pushString($migration->version())
            ->pushString($this->clock->now()->format('Y-m-d H:i:s'))
            ->execute()
        ;
    }

    #[Override]
    public function remove(MigrationInterface $migration): void
    {
        try {
            $this->db
                ->prepare('DELETE FROM MIGRATION_STATUS WHERE MIGRATION_NAME = ?')
                ->pushString($migration->name())
                ->execute()
            ;
        } catch (TableNotFoundException) {
            // No-op
        }
    }

    #[Override]
    public function lastVersion(): ?string
    {
        try {
            /** @var mixed $version */
            $version = $this->db->query('SELECT VERSION FROM MIGRATION_STATUS ORDER BY MIGRATION_DATE DESC LIMIT 1')->fetchColumn(0);

            if ($version === false) {
                return null;
            }

            return (string) $version;
        } catch (TableNotFoundException) {
            return null;
        }
    }
}
