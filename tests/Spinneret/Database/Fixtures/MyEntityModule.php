<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Database\Compiler\SetConnectionCompilerPass;
use Override;

use function Arakne\Spinneret\Database\database_connection;

final class MyEntityModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->service(
            MyEntityRepository::class,
            parameters: [database_connection('test')],
            public: true,
        );
        $this->autowire(
            OtherRepository::class,
            public: true,
            tags: [SetConnectionCompilerPass::TAG => ['connection' => ['test' => 'test', 'other' => 'other']]],
        );
    }
}
