<?php

namespace Arakne\Tests\Spinneret\Translation\Fixtures;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Translation\TranslationModule;
use Override;

class TranslationApplication extends Application
{
    public function configDir(): string
    {
        return __DIR__ . '/config';
    }

    protected function applicationModules(): array
    {
        return [
            TranslationModule::create($this),
            new class implements ModuleInterface {
                #[Override]
                public function register(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->register(Messages::class)->public();
                }
            }
        ];
    }
}
