<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Value\NestedValueInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Override;

use function array_flip;
use function is_array;

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
            $counter = $this->computeUsage($builder);
            $hasRemoved = $this->removeUnusedServices($builder, $counter);
        } while ($hasRemoved); // Continue until no more services are removed (handle nested dependencies)
    }

    private function computeUsage(ContainerBuilder $builder): ServiceCounter
    {
        $counter = new ServiceCounter();

        foreach ($builder->services as $service) {
            $factory = $service->resolveFactory();

            if ($factory instanceof MethodServiceFactory) {
                $this->processValue($builder, $counter, $factory->object);
            }

            /** @var mixed $argument */
            foreach ($service->arguments as $argument) {
                $this->processValue($builder, $counter, $argument);
            }
        }

        return $counter;
    }

    private function removeUnusedServices(ContainerBuilder $builder, ServiceCounter $counter): bool
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

    private function processValue(ContainerBuilder $builder, ServiceCounter $counter, mixed $value): void
    {
        match (true) {
            $value instanceof Reference => $this->processReference($builder, $counter, $value),
            is_array($value) => $this->processArray($builder, $counter, $value),
            $value instanceof NestedValueInterface => $this->processNestedValue($builder, $counter, $value),
            default => null,
        };
    }

    private function processReference(ContainerBuilder $builder, ServiceCounter $counter, Reference $value): void
    {
        $service = $builder->find($value->id);

        if ($service) {
            $counter->add($service);
        }
    }

    private function processArray(ContainerBuilder $builder, ServiceCounter $counter, array $value): void
    {
        /** @var mixed $item */
        foreach ($value as $item) {
            $this->processValue($builder, $counter, $item);
        }
    }

    private function processNestedValue(ContainerBuilder $builder, ServiceCounter $counter, NestedValueInterface $value): void
    {
        foreach ($value->traverse() as $item) {
            $this->processValue($builder, $counter, $item);
        }
    }
}

/**
 * @internal
 */
final class ServiceCounter
{
    public const int PUBLIC = -1;

    /**
     * @var array<string, int>
     */
    public array $services = [];

    public function add(ServiceBuilder $service): void
    {
        if ($service->public) {
            $this->services[$service->id] = self::PUBLIC;
            return;
        }

        $this->services[$service->id] = ($this->services[$service->id] ?? 0) + 1;
    }

    public function unused(string $serviceId): bool
    {
        return ($this->services[$serviceId] ?? 0) === 0;
    }
}
