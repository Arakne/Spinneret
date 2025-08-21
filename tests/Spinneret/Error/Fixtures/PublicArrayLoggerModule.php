<?php

namespace Arakne\Tests\Spinneret\Error\Fixtures;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Override;

class PublicArrayLoggerModule implements ModuleInterface
{
    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ArrayLogger::class)->public();
    }
}
