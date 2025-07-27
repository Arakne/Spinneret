<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Controller;

final readonly class ControllerTag
{
    public function __construct(
        public string $route,
    ) {}
}
