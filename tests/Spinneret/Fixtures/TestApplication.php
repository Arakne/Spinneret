<?php

namespace Arakne\Tests\Spinneret\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Tests\Spinneret\Fixtures\Error\ErrorModule;
use Arakne\Tests\Spinneret\Fixtures\Hello\HelloModule;

class TestApplication extends Application
{
    public function applicationModules(): array
    {
        return [
            new HelloModule(),
            new ErrorModule(),
        ];
    }
}
