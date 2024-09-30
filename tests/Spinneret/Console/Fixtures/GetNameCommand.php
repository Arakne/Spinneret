<?php

namespace Arakne\Tests\Spinneret\Console\Fixtures;

use Symfony\Component\Console\Command\Command;

class GetNameCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('get-name');
        $this->setAliases(['name', 'other']);
    }
}
