<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

final readonly class NullableContainerClass
{
    public function __construct(
        public ?SingleLiteralClass $dep,
    ) {}
}
