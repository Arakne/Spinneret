<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures;

use function iterator_to_array;

class Aggr
{
    public readonly array $services;
    public function __construct(iterable $services)
    {
        $this->services = iterator_to_array($services);
    }
}
