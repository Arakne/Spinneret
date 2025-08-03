<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Override;

/**
 * Replace aliases by their final IDs in service arguments.
 */
final readonly class ResolveAliasesProcessor extends AbstractArgumentProcessor
{
    #[Override]
    protected function processValue(ContainerBuilder $builder, mixed $value): mixed
    {
        if ($value instanceof Reference) {
            return $value->withId($this->resolveAlias($builder, $value->id));
        }

        return $value;
    }

    private function resolveAlias(ContainerBuilder $builder, string $id): string
    {
        while ($next = $builder->aliases[$id] ?? null) {
            $id = $next;
        }

        return $id;
    }
}
