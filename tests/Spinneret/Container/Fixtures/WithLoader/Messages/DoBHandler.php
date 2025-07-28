<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages;

use Arakne\Spinneret\Container\Attribute\Service;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\MessageHandlerTag;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\SimpleDep;

#[Service(tags: [new MessageHandlerTag(DoB::class)])]
final readonly class DoBHandler
{
    public function __construct(
        public SimpleDep $dep,
    ) {}
}
