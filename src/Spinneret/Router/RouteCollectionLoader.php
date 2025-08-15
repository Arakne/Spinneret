<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Override;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads routes from application modules
 */
final readonly class RouteCollectionLoader implements RouteCollectionLoaderInterface
{
    public function __construct(
        /**
         * List of parameter of {@see RouteCollectionBuilder::add()} calls.
         *
         * @var list<list{string, class-string, list<string>, class-string|null}>
         */
        private array $routes = [],
    ) {}

    #[Override]
    public function load(Application $application): RouteCollection
    {
        $builder = new RouteCollectionBuilder();

        foreach ($this->routes as $route) {
            $builder->add(...$route);
        }

        foreach ($application->modules() as $module) {
            // @todo Call only modules that define routes
            $module->configureRoutes($builder);
        }

        return $builder->routes;
    }
}
