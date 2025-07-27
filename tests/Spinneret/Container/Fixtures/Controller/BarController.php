<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Controller;

use Override;

class BarController implements ControllerInterface
{
    #[Override]
    public static function route(): string
    {
        return '/bar';
    }

    public function __invoke(object $request): string
    {
        return 'test bar';
    }
}
