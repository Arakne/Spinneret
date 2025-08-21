<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\ConfigurableModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Download\DownloadModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Error\ErrorModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\RegistrationModule;

class TestApplication extends Application
{
    public function applicationModules(): array
    {
        return [
            new ConsoleModule(),
            new HelloModule(),
            new ErrorModule(),
            new RegistrationModule(),
            new ConfigurableModule(),
            new DownloadModule(),
        ];
    }

    public function configDir(): string
    {
        return __DIR__ . '/config';
    }
}
