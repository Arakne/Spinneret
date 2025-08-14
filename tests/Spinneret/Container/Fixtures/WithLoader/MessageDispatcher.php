<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader;

use Arakne\Spinneret\Container\Attribute\Service;

#[Service(public: true, aliases: ['dispatcher'])]
final readonly class MessageDispatcher
{
    public function __construct(
        public array $handlers = [],
    ) {}
}
