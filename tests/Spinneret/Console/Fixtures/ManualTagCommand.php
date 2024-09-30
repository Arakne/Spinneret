<?php

namespace Arakne\Tests\Spinneret\Console\Fixtures;

use Symfony\Component\Console\Command\Command;

class ManualTagCommand extends Command
{
    public function __construct()
    {
        parent::__construct('manual');
    }
}
