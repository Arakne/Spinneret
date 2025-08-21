<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\DatabaseModule;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Override;

class TestMigrationApp extends Application
{
    public function __construct()
    {
        parent::__construct(true, 'test');
    }

    public function configDir(): string
    {
        return __DIR__ . '/config';
    }

    protected function applicationModules(): array
    {
        return [
            new ConsoleModule(),
            new DatabaseModule(),
            new class () implements ModuleInterface {
                #[Override]
                public function register(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->register(AddEntitiesMigration::class);
                    $containerBuilder->register(CreateStructureMigration::class);
                    $containerBuilder->register(SeparateNameColumnsMigration::class);
                    $containerBuilder->register(SeparateNameColumnsMigration::class);

                    $containerBuilder->alias('migration_manager', MigrationManager::class);
                    $containerBuilder->alias('database', DatabaseConnectionManagerInterface::class);
                }
            },
        ];
    }
}
