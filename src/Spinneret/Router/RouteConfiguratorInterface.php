<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Router\Attribute\Route;

/**
 * Interface for configuring routes in the application.
 * This interface must be implemented by a {@see ModuleInterface}.
 *
 * Note: You can use attributes like {@see Route} to configure routes directly on your DTO classes,
 *       instead of implementing this interface.
 *       Use this interface if you don't want to use import from container or if you need to register routes manually.
 */
interface RouteConfiguratorInterface
{
    /**
     * Register routes
     *
     * Note: this method is only called during the build of the container,
     *       so it's not possible to use dynamic configuration (e.g. feature flags resolved during runtime).
     *
     * @param RouteCollectionBuilder $builder
     * @return void
     */
    public function configureRoutes(RouteCollectionBuilder $builder): void;
}
