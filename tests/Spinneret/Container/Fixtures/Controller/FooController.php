<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Controller;

use Override;

class FooController implements ControllerInterface
{
    #[Override]
    public static function route(): string
    {
        return '/foo';
    }

    #[Override]
    public function __invoke(object $request): string
    {
        return 'test';
    }
}