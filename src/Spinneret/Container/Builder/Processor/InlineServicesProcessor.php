<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Override;

use function array_flip;

/**
 * Inline service instanciation when it is used only once, or it's explicitly marked as inline.
 */
final readonly class InlineServicesProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        //$this->markAsInline($builder); // @todo Activer quand la validation des services sera implémentée
        $this->doInlining($builder);
    }

    private function markAsInline(ContainerBuilder $builder): void
    {
        $reverseAliases = array_flip($builder->aliases);
        $counter = ServiceUsageCounter::fromContainerBuilder($builder);

        foreach ($counter->services as $serviceId => $usageCount) {
            if ($usageCount !== 1 || isset($reverseAliases[$serviceId])) {
                continue;
            }

            $service = $builder->services[$serviceId] ?? null;

            if ($service === null || $service->runtime) {
                continue;
            }

            $service->inline();
        }
    }

    private function doInlining(ContainerBuilder $builder): void
    {
        $processor = new readonly class () extends AbstractArgumentProcessor {
            #[Override]
            protected function processValue(ContainerBuilder $builder, mixed $value): mixed
            {
                if (!$value instanceof Reference) {
                    return $value;
                }

                $service = $builder->find($value->id);

                if ($service === null || $service->inline !== true) {
                    return $value;
                }

                return $service->asInlineValue() ?? $value;
            }
        };

        $processor->process($builder);
    }
}
