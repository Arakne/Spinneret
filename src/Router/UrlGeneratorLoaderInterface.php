<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;

/**
 * Used by {@see RouterModule} to load the URL generator
 */
interface UrlGeneratorLoaderInterface
{
    /**
     * Loads the URL generator
     * The loader may compile and load compiled generator if needed
     *
     * @param Application $application The current application
     *
     * @return UrlGeneratorInterface The loaded URL generator
     */
    public function load(Application $application): UrlGeneratorInterface;
}
