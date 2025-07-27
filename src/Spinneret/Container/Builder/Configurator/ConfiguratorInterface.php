<?php

namespace Arakne\Spinneret\Container\Builder\Configurator;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;

/**
 * Configure a service before apply processors.
 * This is useful to set some properties or metadata on the service, depending on the service type.
 *
 * Unlike processors, configurators are applied on services and not on the container.
 */
interface ConfiguratorInterface
{
    /**
     * Check if the configurator should be applied to the given service.
     *
     * @param ServiceBuilder $service
     * @return bool
     */
    public function supports(ServiceBuilder $service): bool;

    /**
     * Apply the configurator to the given service.
     *
     * @param ServiceBuilder $service
     * @param ContainerBuilder $containerBuilder
     *
     * @return void
     */
    public function configure(ServiceBuilder $service, ContainerBuilder $containerBuilder): void;
}
