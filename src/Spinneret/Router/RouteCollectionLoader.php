<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Override;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads routes from application modules
 */
final class RouteCollectionLoader implements RouteCollectionLoaderInterface
{
    #[Override]
    public function load(Application $application): RouteCollection
    {
        $builder = new RouteCollectionBuilder();

        foreach ($application->modules() as $module) {
            // @todo Call only modules that define routes
            $module->configureRoutes($builder);
        }

        return $builder->routes;
    }
}
