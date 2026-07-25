<?php

namespace Arakne\Spinneret\Container\Builder\Configurator;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Closure;
use Override;
use ReflectionAttribute;

/**
 * Apply configurator when a service has a specific attribute at class level.
 *
 * @template T as object
 */
final readonly class AttributeConfigurator implements ConfiguratorInterface
{
    public function __construct(
        /**
         * @var class-string<T>
         */
        private string $attributeClass,

        /**
         * @var Closure(ServiceBuilder, ContainerBuilder, T): void
         */
        private Closure $configurator,
    ) {}

    #[Override]
    public function supports(ServiceBuilder $service): bool
    {
        $attributes = $service->reflection()?->getAttributes($this->attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes !== null && $attributes !== [];
    }

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $containerBuilder): void
    {
        foreach ($service->reflection()?->getAttributes($this->attributeClass, ReflectionAttribute::IS_INSTANCEOF) ?? [] as $attribute) {
            $instance = $attribute->newInstance();
            ($this->configurator)($service, $containerBuilder, $instance);
        }
    }
}
