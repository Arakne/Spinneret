<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Tests\Spinneret\Application\Fixtures\Error\ErrorModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\RegistrationModule;

class TestApplication extends Application
{
    public function applicationModules(): array
    {
        return [
            new HelloModule(),
            new ErrorModule(),
            new RegistrationModule(),
        ];
    }
}
