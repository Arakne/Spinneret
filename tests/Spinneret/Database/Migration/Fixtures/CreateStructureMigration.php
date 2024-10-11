<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\AbstractMigration;
use Closure;
use DateTimeImmutable;
use Override;

class CreateStructureMigration extends AbstractMigration
{
    #[Override]
    public function up(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');

        if ($output) {
            $output('Creating table `person`');
        }

        $db->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `person` (
                `id` INT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `birth_date` DATE
            )
            SQL
        );
    }

    #[Override]
    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');

        if ($output) {
            $output('Dropping table `person`');
        }

        $db->exec('DROP TABLE `person`');
    }

    #[Override]
    public function date(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-01 00:00:00');
    }

    #[Override]
    public function version(): string
    {
        return '1.0.0';
    }
}
