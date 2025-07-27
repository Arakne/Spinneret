<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Controller;

interface ControllerInterface
{
    public static function route(): string;
    public function __invoke(object $request): string;
}
