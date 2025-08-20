<?php

namespace Arakne\Spinneret\Container\Builder\Configurator;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Override;
use ReflectionAttribute;

/**
 * Apply configurator defined as attribute using {@see ServiceConfiguratorAttributeInterface}.
 *
 * This processor will simply call the {@see ServiceConfiguratorAttributeInterface::configure()} on the attribute instance,
 * passing the service and the container builder as parameters.
 */
final readonly class ServiceConfiguratorAttributeConfigurator implements ConfiguratorInterface
{
    #[Override]
    public function supports(ServiceBuilder $service): bool
    {
        $attributes = $service->reflection()?->getAttributes(ServiceConfiguratorAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF);

        return $attributes !== null && $attributes !== [];
    }

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $containerBuilder): void
    {
        $service->ignorable(false);

        foreach ($service->reflection()?->getAttributes(ServiceConfiguratorAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) ?? [] as $attribute) {
            $instance = $attribute->newInstance();
            $instance->configure($service, $containerBuilder);
        }
    }
}
