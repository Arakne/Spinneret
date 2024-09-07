<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

class FooCommandHandler
{
    public ?FooCommand $lastCommand = null;

    public function __invoke(FooCommand $command): int
    {
        $this->lastCommand = $command;
        return $command->value * 2;
    }
}
