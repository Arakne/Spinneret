<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

final readonly class ShowConfigResponse
{
    public function __construct(
        public string $message,
        public int $computed,
    ) {
    }
}
