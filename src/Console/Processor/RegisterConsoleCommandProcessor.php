<?php

namespace Arakne\Spinneret\Console\Processor;

use Arakne\Spinneret\Console\Console;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Override;
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
            $service->public();

            foreach ($tags as $tag) {
                // @phpstan-ignore instanceof.alwaysTrue
                assert($tag instanceof AsCommand);

                $commandNames = explode('|', $tag->name);

                foreach ($commandNames as $name) {
                    $commandMap[$name] = $service->id;
                }
            }
        }

        $definition->set(1, $commandMap);
    }
}
