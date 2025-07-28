<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages;

use Arakne\Spinneret\Container\Attribute\Service;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\MessageHandlerTag;

#[Service(tags: [new MessageHandlerTag(DoA::class)])]
final readonly class DoAHandler
{

}
