<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

use Psr\Container\ContainerInterface;

final readonly class ContainerWrapper
{
    public function __construct(
        public ContainerInterface $container,
    ) {}
}
