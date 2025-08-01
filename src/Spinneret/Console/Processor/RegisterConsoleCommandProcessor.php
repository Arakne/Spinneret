<?php

namespace Arakne\Spinneret\Console\Processor;

use Arakne\Spinneret\Console\Console;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Override;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

use function assert;
use function explode;

final readonly class RegisterConsoleCommandProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $definition = $builder->services[Console::class];
        $commandMap = [];

        foreach ($builder->findByTag(AsCommand::class) as $service => $tags) {
            $service->public = true;

            foreach ($tags as $tag) {
                assert($tag instanceof AsCommand);

                $commandNames = explode('|', $tag->name);

                foreach ($commandNames as $name) {
                    $commandMap[$name] = $service->id;
                }
            }
        }

        $definition->arguments[1] = $commandMap;
    }
    private function resolveCommandNames(ContainerBuilder $container, string $serviceId): array|null
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection = new ReflectionClass($container->getDefinition($serviceId)->getClass() ?? $serviceId);

            foreach ($reflection->getAttributes(AsCommand::class) as $attribute) {
                return [$attribute->newInstance()->name];
            }

            /** @var Command $command */
            $command = $container->get($serviceId);

            return [$command->getName(), ...$command->getAliases()];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
