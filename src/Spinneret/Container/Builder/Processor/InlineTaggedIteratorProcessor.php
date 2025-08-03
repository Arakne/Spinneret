<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\TaggedServiceIterator;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

/**
 * Replace {@see TaggedServiceIterator} by an inline array of {@see Reference} to the tagged services.
 */
final readonly class InlineTaggedIteratorProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            /** @var mixed $argument */
            foreach ($service->arguments as $key => $argument) {
                if (!$argument instanceof TaggedServiceIterator) {
                    continue;
                }

                $values = [];

                foreach ($builder->findByTag($argument->tag) as $taggedService => $_) {
                    $values[] = new Reference($taggedService->id);
                }

                $service->arguments[$key] = new DynamicArray($values);
            }
        }
    }
}
