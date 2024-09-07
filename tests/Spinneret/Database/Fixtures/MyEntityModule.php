<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Database\Compiler\SetConnectionCompilerPass;
use Override;

final class MyEntityModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->autowire(
            MyEntityRepository::class,
            public: true,
            tags: [SetConnectionCompilerPass::TAG => ['connection' => 'test']],
        );
    }
}
