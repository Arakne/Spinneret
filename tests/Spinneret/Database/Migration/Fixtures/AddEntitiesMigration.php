<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\AbstractMigration;
use Closure;
use DateTimeImmutable;
use Override;

class AddEntitiesMigration extends AbstractMigration
{
    #[Override]
    public function up(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');
        $db->exec('REPLACE INTO `person` (`id`, `name`, `birth_date`) VALUES (1, "Alice Smith", "1991-02-21")');
        $db->exec('REPLACE INTO `person` (`id`, `name`, `birth_date`) VALUES (2, "Bob Johnson", "1992-03-22")');
        $db->exec('REPLACE INTO `person` (`id`, `name`, `birth_date`) VALUES (3, "Charlie Brown", "1993-04-23")');
    }

    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');
        $db->exec('DELETE FROM `person` WHERE `id` IN (1, 2, 3)');
    }

    #[Override]
    public function date(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-02 00:00:00');
    }

    #[Override]
    public function version(): string
    {
        return '1.0.1';
    }
}
