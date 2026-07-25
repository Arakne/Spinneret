<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Override;

use function array_flip;

/**
 * Remove declared services without usage.
 *
 * A service is considered unused if:
 * - It is not public (not accessible outside the container).
 * - It is not referenced by any other service.
 * - It has no aliases.
 */
final readonly class RemoveUnusedServicesProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        do {
            $counter = ServiceUsageCounter::fromContainerBuilder($builder);
            $hasRemoved = $this->removeUnusedServices($builder, $counter);
        } while ($hasRemoved); // Continue until no more services are removed (handle nested dependencies)
    }

    private function removeUnusedServices(ContainerBuilder $builder, ServiceUsageCounter $counter): bool
    {
        $reverseAliases = array_flip($builder->aliases);
        $hasRemoved = false;

        foreach ($builder->services as $serviceId => $service) {
            // Remove all services that are not used and without aliases
            if (!$service->public && $counter->unused($serviceId) && !isset($reverseAliases[$serviceId])) {
                $builder->remove($serviceId);
                $hasRemoved = true;
            }
        }

        return $hasRemoved;
    }
}
