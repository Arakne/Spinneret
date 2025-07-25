<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Tagged;

final readonly class ComplexTag
{
    public function __construct(
        public int $priority,
    ) {}
}
