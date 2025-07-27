<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class EventListener
{
    public function __construct(
        public string $event,
    ) {}
}
