<?php

namespace Arakne\Spinneret\Console;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\Compiler\RegisterConsoleCommandCompilerPass;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
            new AbstractArgument('Command map should be register by RegisterConsoleCommandCompilerPass'),
        ], public: true);
    }

    #[Override]
    protected function configureContainer(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new RegisterConsoleCommandCompilerPass());
        $containerBuilder->registerAttributeForAutoconfiguration(AsCommand::class, function (ChildDefinition $definition, AsCommand $attribute, \Reflector $reflector): void {
            $definition->addTag(Command::class, ['command' => $attribute->name]);
            $definition->setPublic(true);
        });

        $containerBuilder->register(CacheClearCommand::class, CacheClearCommand::class)
            ->setAutoconfigured(true)
            ->setArguments([service(Application::class)])
        ;

        $containerBuilder->register(DebugConfigCommand::class, DebugConfigCommand::class)
            ->setAutoconfigured(true)
            ->setArguments([service(Application::class)])
        ;
    }
}
