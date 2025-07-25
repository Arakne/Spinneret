<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

final readonly class ResolveAliasesProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            /** @var mixed $argument */
            foreach ($service->arguments as $index => $argument) {
                if ($argument instanceof Reference) {
                    $service->arguments[$index] = new Reference($this->resolveAlias($builder, $argument->id));
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
