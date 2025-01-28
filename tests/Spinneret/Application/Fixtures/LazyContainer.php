<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures;

use Closure;

class LazyContainer
{
    public function __construct(public readonly Closure $ref) {}
}
