<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Controller;

readonly class FrontController
{
    public function __construct(
        public array $controllers = [],
    ) {}

    public function run(string $route, object $request): string
    {
        return $this->controllers[$route]($request);
    }
}
