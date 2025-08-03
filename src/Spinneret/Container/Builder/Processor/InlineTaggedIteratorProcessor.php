<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\TaggedServiceIterator;
use Override;

/**
 * Replace {@see TaggedServiceIterator} by an inline array of {@see Reference} to the tagged services.
 */
final readonly class InlineTaggedIteratorProcessor extends AbstractArgumentProcessor
{
    #[Override]
    protected function processValue(ContainerBuilder $builder, mixed $value): mixed
    {
        if ($value instanceof TaggedServiceIterator) {
            $value = $this->processTaggedServiceIterator($builder, $value);
        }

        return $value;
    }

    private function processTaggedServiceIterator(ContainerBuilder $builder, TaggedServiceIterator $argument): DynamicArray
    {
        $values = [];

        foreach ($builder->findByTag($argument->tag) as $taggedService => $_) {
            $values[] = new Reference($taggedService->id);
        }

        return new DynamicArray($values);
    }
}
