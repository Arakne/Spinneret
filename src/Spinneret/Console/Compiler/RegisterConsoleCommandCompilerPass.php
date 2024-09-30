<?php

namespace Arakne\Spinneret\Console\Compiler;

use Arakne\Spinneret\Console\Console;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function explode;

final readonly class RegisterConsoleCommandCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $tags = $container->findTaggedServiceIds(Command::class);
        $commandMap = [];

        foreach ($tags as $id => $attributes) {
            /** @var string|null $commandName */
            $commandName = $attributes[0]['command'] ?? null;

            if ($commandName === null) {
                try {
                    /** @var Command $command */
                    $command = $container->get($id);
                    $commandNames = [$command->getName(), ...$command->getAliases()];
                } catch (\Throwable) {
                    continue;
                }
            } else {
                $commandNames = explode('|', $commandName);
            }

            foreach ($commandNames as $name) {
                $commandMap[$name] = $id;
            }

            $container->getDefinition($id)->setPublic(true);
        }

        $container->findDefinition(Console::class)->setArgument(1, $commandMap);
    }
}
