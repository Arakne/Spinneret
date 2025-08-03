<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

/**
 * Replace aliases by their final IDs in service arguments.
 */
final readonly class ResolveAliasesProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            /** @var mixed $argument */
            foreach ($service->arguments as $index => $argument) {
                if ($argument instanceof Reference) {
                    $service->arguments[$index] = $argument->withId($this->resolveAlias($builder, $argument->id));
                }
            }
        }
    }

    private function resolveAlias(ContainerBuilder $builder, string $id): string
    {
        while ($next = $builder->aliases[$id] ?? null) {
            $id = $next;
        }

        return $id;
    }
}
