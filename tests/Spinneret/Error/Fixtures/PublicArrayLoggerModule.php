<?php

namespace Arakne\Tests\Spinneret\Error\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Override;

class PublicArrayLoggerModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->service(ArrayLogger::class, public: true);
    }
}
