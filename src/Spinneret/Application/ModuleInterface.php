<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\ViewRendererInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Base type for register routes, presenters, renderers and services on the application
 * The implementation should be stateless and immutable.
 *
 * For a more convenient way to declare module use {@see AbstractModule}
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

    /**
     * Define map of request class to it's handling presenter
     * The present must be declared as a public service in the container.
     *
     * @return array<class-string, class-string<PresenterInterface>>
     */
    public function presenters(): array;

    /**
     * Define map of response class to it's rendering class.
     * The renderer must be declared as a public service in the container.
     *
     * @return array<class-string, class-string<ViewRendererInterface>>
     */
    public function renderers(): array;
}
