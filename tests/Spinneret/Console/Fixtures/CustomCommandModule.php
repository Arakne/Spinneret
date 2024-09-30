<?php

namespace Arakne\Tests\Spinneret\Console\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Symfony\Component\Console\Command\Command;

class CustomCommandModule extends AbstractModule
{
    #[\Override] protected function configure(): void
    {
        $this->autowire(HelloCommand::class);
        $this->autowire(ManualTagCommand::class, tags: [Command::class => ['command' => 'manual']]);
        $this->autowire(GetNameCommand::class, tags: [Command::class]);
    }
}
