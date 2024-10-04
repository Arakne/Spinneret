<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\Migration\AbstractMigration;
use Closure;
use DateTimeImmutable;
use Override;

class SkippedMigration extends AbstractMigration
{
    #[Override]
    public function supports(DatabaseConnectionManagerInterface $connections): bool
    {
        return false;
    }

    #[Override]
    public function up(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        throw new \BadMethodCallException();
    }

    #[Override]
    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        throw new \BadMethodCallException();
    }

    #[Override]
    public function date(): DateTimeImmutable
    {
        return new DateTimeImmutable('2021-01-03 00:00:00');
    }

    #[Override]
    public function version(): string
    {
        return '1.0.1';
    }
}
