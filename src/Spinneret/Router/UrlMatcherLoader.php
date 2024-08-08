<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompilerInterface;
use Override;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

// @todo introduce "compilation step" tag and interface

/**
 * Loads routes from using {@see RouteCollectionLoaderInterface} and compiles them
 */
final readonly class UrlMatcherLoader implements UrlMatcherLoaderInterface
{
    public function __construct(
        private RouteCollectionLoaderInterface $routesLoader,
        private RequestContext $requestContext,
        private ?UrlMatcherCompilerInterface $compiler = null,
    ) {
    }

    #[Override]
    public function load(Application $application): UrlMatcherInterface
    {
        if (!$application->isDev && $matcher = $this->compiler?->load($application, $this->requestContext)) {
            return $matcher;
        }

        $routes = $this->routesLoader->load($application);
        $this->compiler?->compile($application, $routes);

        return new UrlMatcher($routes, $this->requestContext);
    }
}
