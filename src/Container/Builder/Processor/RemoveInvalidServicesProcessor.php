<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

/**
 * Remove all invalid services marked with `ignoreIfInvalid`.
 */
final readonly class RemoveInvalidServicesProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $id => $service) {
            if ($service->ignoreIfInvalid && !$service->validate($builder)) {
                $builder->remove($id);
            }
        }
    }
}
