<?php

namespace Arakne\Spinneret\Application;

/**
 * Interface for modules that need to perform some actions before the application starts.
 */
interface BootableModuleInterface extends ModuleInterface
{
    /**
     * Boot the module.
     *
     * @param Application $application The current application
     */
    public function boot(Application $application): void;
}
