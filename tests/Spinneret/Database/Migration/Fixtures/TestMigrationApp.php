<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
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
            new class () extends AbstractModule {
                #[Override]
                protected function configure(): void
                {
                    $this->autowire(AddEntitiesMigration::class);
                    $this->autowire(CreateStructureMigration::class);
                    $this->autowire(SeparateNameColumnsMigration::class);
                    $this->autowire(SeparateNameColumnsMigration::class);
                }

                #[Override]
                protected function configureContainer(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->alias('migration_manager', MigrationManager::class);
                    $containerBuilder->alias('database', DatabaseConnectionManagerInterface::class);
                }
            },
        ];
    }
}
