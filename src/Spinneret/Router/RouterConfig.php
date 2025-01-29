<?php

namespace Arakne\Spinneret\Router;

final readonly class RouterConfig
{
    public function __construct(
        public ?string $baseUrl = null,
    ) {}
}
