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
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

use function class_exists;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function var_export;

/**
 * Default implementation of {@see UrlGeneratorCompilerInterface}.
 * It compiles routes to a single PHP file.
 */
final readonly class UrlGeneratorCompiler implements UrlGeneratorCompilerInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private string $targetFile = 'url_generator_routes.php',
        private string $targetFieldsFile = 'url_generator_fields.php',
    ) {}

    #[Override]
    public function load(Application $application, RequestContext $context): ?UrlGeneratorInterface
    {
        $generator = $this->loadCompiledGenerator($application, $context);

        if (!$generator) {
            return null;
        }

        return new UrlGenerator(
            $generator,
            $this->formFactory,
            $this->loadCompiledExportedFields($application) ?? [],
        );
    }

    #[Override]
    public function compile(Application $application, RouteCollection $routes): void
    {
        $routesToCompile = clone $routes;
        $compiledRoutes = $this->compileRoutes($routesToCompile);
        $compiledFields = $this->compileFields($routesToCompile);

        Files::write($application->cacheDir() . '/' . $this->targetFile, $compiledRoutes);
        Files::write($application->cacheDir() . '/' . $this->targetFieldsFile, $compiledFields);
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
     * Compile the exported fields as PHP code
     *
     * @param RouteCollection $routes
     * @return string
     */
    private function compileFields(RouteCollection $routes): string
    {
        $exportedFieldsByRequest = [];

        foreach ($routes->all() as $route) {
            $request = $route->getDefault('_target');

            if (is_string($request) && class_exists($request)) {
                $exportedFieldsByRequest[$request] = UrlGenerator::computedExportedFields($request, self::useQueryString($route));
            }
        }

        $exportedFieldsByRequestPhp = var_export($exportedFieldsByRequest, true);

        return <<<PHP
            <?php

            // Generated file: do not modify
            return {$exportedFieldsByRequestPhp};
            PHP;
    }

    private function loadCompiledGenerator(Application $application, RequestContext $context): ?CompiledUrlGenerator
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

            return new CompiledUrlGenerator($compiledRoutes, $context);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param Application $application
     * @return array<class-string, array<string, true>>|null
     */
    private function loadCompiledExportedFields(Application $application): ?array
    {
        $cacheFile = $application->cacheDir() . '/' . $this->targetFieldsFile;

        if (!is_file($cacheFile)) {
            return null;
        }

        try {
            $fields = require $cacheFile;

            if (!is_array($fields)) {
                return null;
            }

            /** @var array<class-string, array<string, true>> */
            return $fields;
        } catch (Throwable) {
            return null;
        }
    }

    private static function useQueryString(Route $route): bool
    {
        foreach ($route->getMethods() as $method) {
            if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS' || $method === 'DELETE') {
                return true;
            }
        }

        return false;
    }
}
