<?php

namespace Arakne\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\UrlGenerator;
use Arakne\Spinneret\Router\UrlGeneratorInterface;
use Arakne\Spinneret\Util\Files;
use Override;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\Generator\Dumper\CompiledUrlGeneratorDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

use function is_array;
use function is_file;

/**
 * Default implementation of {@see UrlGeneratorCompilerInterface}.
 * It compiles routes to a single PHP file.
 */
final readonly class UrlGeneratorCompiler implements UrlGeneratorCompilerInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private string $targetFile = 'url_generator_routes.php',
    ) {}

    #[Override]
    public function load(Application $application, RequestContext $context): ?UrlGeneratorInterface
    {
        $cacheFile = $application->cacheDir() . '/' . $this->targetFile;

        if (!is_file($cacheFile)) {
            return null;
        }

        try {
            $compiledRoutes = require $cacheFile;

            if (!is_array($compiledRoutes)) {
                return null;
            }

            return new UrlGenerator(new CompiledUrlGenerator($compiledRoutes, $context), $this->formFactory);
        } catch (Throwable) {
            return null;
        }
    }

    #[Override]
    public function compile(Application $application, RouteCollection $routes): void
    {
        $routesToCompile = clone $routes;

        $compiledRoutes = $this->compileRoutes($routesToCompile);

        $this->save($application, $compiledRoutes);
    }

    /**
     * Compile the routes and return the PHP code
     *
     * @param RouteCollection $routes
     * @return string
     */
    private function compileRoutes(RouteCollection $routes): string
    {
        $dumper = new CompiledUrlGeneratorDumper($routes);
        return $dumper->dump();
    }

    /**
     * Save the compiled routes to the cache directory
     *
     * @param Application $application
     * @param string $content
     *
     * @return void
     */
    private function save(Application $application, string $content): void
    {
        $cacheFile = $application->cacheDir() . '/' . $this->targetFile;

        Files::write($cacheFile, $content);
    }
}
