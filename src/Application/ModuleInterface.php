<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;

/**
 * Base type for register routes, presenters, renderers and services on the application
 * The implementation should be stateless and immutable.
 *
 * @see ConfigurableModuleInterface for a module that can be configured
 */
interface ModuleInterface
{
    /**
     * Register all required services in the container
     *
     * Note: This method is only called during the build of the container,
     *       so it's not possible to use dynamic configuration directly.
     *       To use a dynamic configuration you should use a factory that takes the configuration as argument.
     *
     * @param ContainerBuilder $containerBuilder
     *
     * @return void
     */
    public function register(ContainerBuilder $containerBuilder): void;
}
