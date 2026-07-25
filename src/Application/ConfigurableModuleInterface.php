<?php

namespace Arakne\Spinneret\Application;

/**
 * Base type for module which can be configured
 *
 * @template C as object
 */
interface ConfigurableModuleInterface extends ModuleInterface
{
    /**
     * Define the configuration object for the module
     * A new instance should be returned with the new configuration
     *
     * @param C $configuration The new configuration object
     * @return static New instance with configuration
     */
    public function withConfiguration(object $configuration): static;

    /**
     * Get the current configuration object
     * If {@see withConfiguration()} was not called before, a default configuration should be returned
     *
     * @return C
     */
    public function configuration(): object;
}
