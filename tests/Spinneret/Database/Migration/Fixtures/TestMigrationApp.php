<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Database\DatabaseConnectionManagerInterface;
use Arakne\Spinneret\Database\DatabaseModule;
use Arakne\Spinneret\Database\Migration\MigrationManager;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
                    $containerBuilder->setAlias('migration_manager', MigrationManager::class)->setPublic(true);
                    $containerBuilder->setAlias('database', DatabaseConnectionManagerInterface::class)->setPublic(true);
                }
            },
        ];
    }
}
