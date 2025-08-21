<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

use function Arakne\Spinneret\Database\database_connection;

final class MyEntityModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(MyEntityRepository::class, [database_connection('test')])->public();
        $containerBuilder->register(OtherRepository::class, [database_connection('test'), database_connection('other')])->public();
    }
}
