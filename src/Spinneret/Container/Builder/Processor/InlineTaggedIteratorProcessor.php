<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Argument\DynamicArray;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Argument\TaggedServiceIterator;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

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
