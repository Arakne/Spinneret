<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\WithLoader;

final readonly class MessageHandlerTag
{
    public function __construct(
        public string $message,
    ) {}
}
