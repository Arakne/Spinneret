<?php

namespace Arakne\Spinneret\Container\Attribute;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;

/**
 * Base type for attributes that can automatically configure the marked service.
 *
 * Those attributes will be handled by the {@see ServiceConfiguratorAttributeConfigurator} configurator,
 * which is registered by default in the container builder.
 *
 * Attribute implementing this interface must have, at least, the target set to {@see Attribute::TARGET_CLASS}.
 */
interface ServiceConfiguratorAttributeInterface
{
    /**
     * Configure the marked service.
     *
     * @param ServiceBuilder $service The current service being configured.
     * @param ContainerBuilder $container The container builder where the service is being configured.
     *
     * @return void
     */
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void;
}
