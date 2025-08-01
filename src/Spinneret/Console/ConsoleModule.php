<?php

namespace Arakne\Spinneret\Console;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\Processor\RegisterConsoleCommandProcessor;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;

use function Arakne\Spinneret\Application\service;

/**
 * Module for enable the console
 *
 * To use the console, simply create the application with the ConsoleModule (registered on top),
 * and use `Application::get(Console::class)` to get the console instance, and call `run()` on it.
 *
 * To register a command, create a class that extends `Command` and add the `AsCommand` attribute to it.
 * You can also manually tag the command using `Command::class` as tag, and the `command` attribute with the command name.
 *
 * Provided services:
 * - {@see Console} - The console application
 * - {@see CacheClearCommand} - Command to clear the cache
 * - {@see DebugConfigCommand} - Command to show the configuration
 */
final class ConsoleModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->service(Console::class, [
            service(Application::class),
            [],
        ], public: true);
    }

    #[Override]
    protected function configureContainer(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->processor(new RegisterConsoleCommandProcessor());
        $containerBuilder->configureAttribute(AsCommand::class, static function (ServiceBuilder $service, ContainerBuilder $builder, AsCommand $attribute): void {
            $service->tag($attribute)->public();
        });

        $containerBuilder->register(CacheClearCommand::class, [service(Application::class)]);
        $containerBuilder->register(DebugConfigCommand::class, [service(Application::class)]);
    }
}
