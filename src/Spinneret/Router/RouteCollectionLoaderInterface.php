<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Symfony\Component\Routing\RouteCollection;

/**
 * Used by {@see RouterModule} to load routes
 */
interface RouteCollectionLoaderInterface
{
    /**
     * Loads application routes
     *
     * @param Application $application The current application
     *
     * @return RouteCollection Built route collection
     */
    public function load(Application $application): RouteCollection;
}
