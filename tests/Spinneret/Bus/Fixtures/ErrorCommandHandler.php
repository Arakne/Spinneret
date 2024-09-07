<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

class ErrorCommandHandler
{
    public function __invoke(ErrorCommand $command): void
    {
        throw new \DomainException('Error');
    }
}
