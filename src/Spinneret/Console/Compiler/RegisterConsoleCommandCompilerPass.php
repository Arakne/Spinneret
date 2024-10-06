<?php

namespace Arakne\Spinneret\Console\Compiler;

use Arakne\Spinneret\Console\Console;
use Override;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function explode;
use function is_string;

final readonly class RegisterConsoleCommandCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $tags = $container->findTaggedServiceIds(Command::class);
        $commandMap = [];

        foreach ($tags as $id => $attributes) {
            /** @var string|list<string>|null $commandNames */
            $commandNames = $attributes[0]['command'] ?? $this->resolveCommandNames($container, $id);

            if ($commandNames === null) {
                continue;
            }

            if (is_string($commandNames)) {
                $commandNames = explode('|', $commandNames);
            }

            foreach ($commandNames as $name) {
                $commandMap[$name] = $id;
            }

            $container->getDefinition($id)->setPublic(true);
        }

        $container->findDefinition(Console::class)->setArgument(1, $commandMap);
    }

    private function resolveCommandNames(ContainerBuilder $container, string $serviceId): array|string|null
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection = new ReflectionClass($container->getDefinition($serviceId)->getClass() ?? $serviceId);

            foreach ($reflection->getAttributes(AsCommand::class) as $attribute) {
                return $attribute->newInstance()->name;
            }

            /** @var Command $command */
            $command = $container->get($serviceId);

            return [$command->getName(), ...$command->getAliases()];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
