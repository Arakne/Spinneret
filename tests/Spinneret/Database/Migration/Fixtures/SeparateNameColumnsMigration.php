<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\AbstractMigration;
use Closure;
use DateTimeImmutable;
use Override;

class SeparateNameColumnsMigration extends AbstractMigration
{
    #[Override]
    public function up(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');
        $db->exec('ALTER TABLE `person` ADD COLUMN `first_name` VARCHAR(255) NOT NULL DEFAULT ""');
        $db->exec('ALTER TABLE `person` ADD COLUMN `last_name` VARCHAR(255)');

        foreach ($db->query('SELECT `id`, `name` FROM `person`')->asAssociativeArray() as $row) {
            if ($output) {
                $output('Processing person ' . $row['id']);
            }

            $nameParts = explode(' ', $row['name'], 2);

            $updateStmt = $db->prepare('UPDATE `person` SET `first_name` = ?, `last_name` = ? WHERE `id` = ?');
            $updateStmt->pushString($nameParts[0]);

            if (count($nameParts) === 1) {
                $updateStmt->pushNull();
            } else {
                $updateStmt->pushString($nameParts[1]);
            }

            $updateStmt->pushInt((int) $row['id']);
            $updateStmt->execute();
        }

        $db->exec('ALTER TABLE `person` DROP COLUMN `name`');
    }

    #[Override]
    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        $db = $connections->get('test');
        $db->exec('ALTER TABLE `person` ADD COLUMN `name` VARCHAR(255) NOT NULL DEFAULT ""');

        foreach ($db->query('SELECT `id`, `first_name`, `last_name` FROM `person`')->asAssociativeArray() as $row) {
            if ($output) {
                $output('Processing person ' . $row['id']);
            }

            $name = $row['first_name'];

            if ($row['last_name'] !== null) {
                $name .= ' ' . $row['last_name'];
            }

            $updateStmt = $db->prepare('UPDATE `person` SET `name` = ? WHERE `id` = ?');
            $updateStmt->pushString($name);
            $updateStmt->pushInt((int) $row['id']);
            $updateStmt->execute();
        }

        $db->exec('ALTER TABLE `person` DROP COLUMN `first_name`');
        $db->exec('ALTER TABLE `person` DROP COLUMN `last_name`');
    }

    #[Override]
    public function date(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-02-12 00:00:00');
    }

    #[Override]
    public function version(): string
    {
        return '1.2.0';
    }
}
