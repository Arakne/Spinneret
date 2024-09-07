<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Symfony\Component\Routing\Generator\UrlGenerator as SfUrlGenerator;
use Symfony\Component\Routing\RequestContext;

// @todo interface + tests
class UrlGeneratorLoader
{
    public function __construct(
        private RouteCollectionLoaderInterface $routesLoader,
        private RequestContext $requestContext,
        //private ?UrlMatcherCompilerInterface $compiler = null,
    ) {
    }

    public function load(Application $application): UrlGeneratorInterface
    {
        //if (!$application->isDev && $matcher = $this->compiler?->load($application, $this->requestContext)) {
        //    return $matcher;
        //}
        //
        $routes = $this->routesLoader->load($application);
        //$this->compiler?->compile($application, $routes);

        // @todo handle compilation
        $sfGenerator = new SfUrlGenerator($routes, $this->requestContext);

        return new UrlGenerator($sfGenerator);
    }
}
