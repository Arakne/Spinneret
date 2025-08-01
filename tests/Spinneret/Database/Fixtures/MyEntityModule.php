<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
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
        // @todo test autowiring of repositories with database connection
        $this->service(
            OtherRepository::class,
            parameters: [database_connection('test'), database_connection('other')],
            public: true,
        );
    }
}
