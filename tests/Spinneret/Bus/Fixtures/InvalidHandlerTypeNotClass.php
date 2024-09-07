<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

class InvalidHandlerTypeNotClass
{
    public function __invoke(object $command): void
    {
        // TODO: Implement __invoke() method.
    }
}
