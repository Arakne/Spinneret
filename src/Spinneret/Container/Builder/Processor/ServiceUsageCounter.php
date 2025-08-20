<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Value\NestedValueInterface;
use Arakne\Spinneret\Container\Value\Reference;

use function is_array;

/**
 * Utility class to count service usage in a container.
 *
 * @internal
 */
final class ServiceUsageCounter
{
    public const int PUBLIC = -1;

    /**
     * Map of service IDs to their usage count.
     * {@see self::PUBLIC} is used to mark public services (which cannot be counted).
     *
     * @var array<string, int>
     */
    public private(set) array $services = [];

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

    public static function fromContainerBuilder(ContainerBuilder $builder): self
    {
        $counter = new self();

        foreach ($builder->services as $service) {
            if ($service->value !== null) {
                self::processValue($builder, $counter, $service->value);
                continue;
            }

            $factory = $service->resolveFactory();

            if ($factory instanceof MethodServiceFactory) {
                self::processValue($builder, $counter, $factory->object);
            }

            /** @var mixed $argument */
            foreach ($service->arguments as $argument) {
                self::processValue($builder, $counter, $argument);
            }
        }

        return $counter;
    }

    private static function processValue(ContainerBuilder $builder, self $counter, mixed $value): void
    {
        match (true) {
            $value instanceof Reference => self::processReference($builder, $counter, $value),
            is_array($value) => self::processArray($builder, $counter, $value),
            $value instanceof NestedValueInterface => self::processNestedValue($builder, $counter, $value),
            default => null,
        };
    }

    private static function processReference(ContainerBuilder $builder, self $counter, Reference $value): void
    {
        $service = $builder->find($value->id);

        if ($service) {
            $counter->add($service);
        }
    }

    private static function processArray(ContainerBuilder $builder, self $counter, array $value): void
    {
        /** @var mixed $item */
        foreach ($value as $item) {
            self::processValue($builder, $counter, $item);
        }
    }

    private static function processNestedValue(ContainerBuilder $builder, self $counter, NestedValueInterface $value): void
    {
        foreach ($value->traverse() as $item) {
            self::processValue($builder, $counter, $item);
        }
    }
}
