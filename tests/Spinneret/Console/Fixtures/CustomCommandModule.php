<?php

namespace Arakne\Tests\Spinneret\Console\Fixtures;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Symfony\Component\Console\Attribute\AsCommand;

class CustomCommandModule implements ModuleInterface
{
    #[\Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HelloCommand::class);
        $containerBuilder->register(ManualTagCommand::class)->tag(new AsCommand('manual'));
    }
}
