<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

class InvalidHandlerTooManyParameters
{
    public function __invoke(FooCommand $command, FooCommand $other): void
    {
        // TODO: Implement __invoke() method.
    }
}
