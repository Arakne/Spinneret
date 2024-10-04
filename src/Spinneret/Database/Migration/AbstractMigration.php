<?php

namespace Arakne\Spinneret\Database\Migration;

use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Closure;
use Override;

use function strrpos;
use function substr;

abstract class AbstractMigration implements MigrationInterface
{
    #[Override]
    public function supports(DatabaseConnectionManagerInterface $connections): bool
    {
        return true;
    }

    #[Override]
    public function name(): string
    {
        $className = static::class;

        if (($pos = strrpos($className, '\\')) !== false) {
            $className = substr($className, $pos + 1);
        }

        return $className;
    }

    #[Override]
    public function down(DatabaseConnectionManagerInterface $connections, ?Closure $output = null): void
    {
        // No-op: some migrations may not need a down method
    }
}
