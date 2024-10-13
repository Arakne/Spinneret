<?php

namespace Arakne\Tests\Spinneret\Translation\Fixtures;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Translation\TranslationModule;

class TranslationApplication extends Application
{
    public function configDir(): string
    {
        return __DIR__ . '/config';
    }

    protected function applicationModules(): array
    {
        return [
            new TranslationModule(),
            new class extends AbstractModule {
                protected function configure(): void
                {
                    $this->autowire(Messages::class, public: true);
                }
            }
        ];
    }
}
