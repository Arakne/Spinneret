<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Closure;
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
        $this->markAsInline($builder);
        do {
            $hasChanged = $this->doInlining($builder);
        } while ($hasChanged);
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

            if ($service === null || $service->runtime || $service->inline !== null) {
                continue;
            }

            $service->inline();
        }
    }

    private function doInlining(ContainerBuilder $builder): bool
    {
        $hasChanged = false;
        $processor = new readonly class (
            function () use (&$hasChanged): void {
                $hasChanged = true;
            }
        ) extends AbstractArgumentProcessor {
            public function __construct(
                private Closure $notifyChange,
            ) {}

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

                $inlined = $service->asInlineValue($builder);

                if ($inlined === null) {
                    return $value;
                }

                ($this->notifyChange)();
                return $inlined;
            }
        };

        $processor->process($builder);

        return $hasChanged;
    }
}
