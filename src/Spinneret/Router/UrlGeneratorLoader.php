<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompilerInterface;
use Override;
use Symfony\Component\Routing\Generator\UrlGenerator as SfUrlGenerator;
use Symfony\Component\Routing\RequestContext;

/**
 * Load the URL generator for the application.
 */
final readonly class UrlGeneratorLoader implements UrlGeneratorLoaderInterface
{
    public function __construct(
        private RouteCollectionLoaderInterface $routesLoader,
        private RequestContext $requestContext,
        private ?UrlGeneratorCompilerInterface $compiler = null,
    ) {
    }

    #[Override]
    public function load(Application $application): UrlGeneratorInterface
    {
        if (!$application->isDev && $matcher = $this->compiler?->load($application, $this->requestContext)) {
            return $matcher;
        }

        $routes = $this->routesLoader->load($application);
        $this->compiler?->compile($application, $routes);

        $sfGenerator = new SfUrlGenerator($routes, $this->requestContext);

        return new UrlGenerator($sfGenerator);
    }
}
