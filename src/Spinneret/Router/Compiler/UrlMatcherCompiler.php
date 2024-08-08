<?php

namespace Arakne\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Override;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

use function dirname;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function md5;
use function mkdir;
use function serialize;
use function var_export;

/**
 * Default implementation of {@see UrlMatcherCompilerInterface}.
 * It compiles routes and fields extractors into a single PHP file.
 */
final readonly class UrlMatcherCompiler implements UrlMatcherCompilerInterface
{
    public function __construct(
        private string $targetFile = 'compiled_routes.php',
    ) {
    }

    #[Override]
    public function load(Application $application, RequestContext $context): ?UrlMatcherInterface
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

            return new CompiledUrlMatcher($compiledRoutes, $context);
        } catch (Throwable) {
            return null;
        }
    }

    #[Override]
    public function compile(Application $application, RouteCollection $routes): void
    {
        $routesToCompile = clone $routes;

        $fieldExtractorClass = $this->compileFieldExtractorClass($routesToCompile);
        $compiledRoutes = $this->compileRoutes($routesToCompile);

        $content = str_replace('<?php', '<?php' . PHP_EOL . PHP_EOL . $fieldExtractorClass, $compiledRoutes);

        $this->save($application, $content);
    }

    /**
     * Compile the field extractor class and modify _fields_extractor attribute of routes
     * This method must be called before compiling the routes
     *
     * @param RouteCollection $routes Routes to compile. This parameter will be modified by the compiler, so it should be cloned before being passed.
     *
     * @return string
     */
    private function compileFieldExtractorClass(RouteCollection $routes): string
    {
        $fieldExtractorClassName = 'GeneratedFieldsExtractor' . md5(serialize($routes));
        $body = '';
        $caseIndent = '                ';

        foreach ($routes as $route) {
            $target = $route->getDefault('_target');
            $extractor = $route->getDefault('_fields_extractor');

            if (!$target || !$extractor) {
                continue;
            }

            $extractor = new $extractor($target);

            $body .= $caseIndent . var_export($target, true) . ' => ' . $extractor->compile($route->getMethods()[0] ?? 'GET') . ',' . PHP_EOL;

            $route->setDefault('_fields_extractor', $fieldExtractorClassName);
        }

        return <<<PHP
if (!class_exists($fieldExtractorClassName::class)) {
    final readonly class $fieldExtractorClassName
    {
        public function __construct(
            private string \$requestClassName,
        ) {
        }
    
        public function __invoke(\Psr\Http\Message\ServerRequestInterface \$request): array
        {
            \$extractor = match (\$this->requestClassName) {
{$body}
                default => throw new \RuntimeException("No fields extractor found for class \$this->requestClassName"),
            };
    
            return \$extractor(\$request);
        }
    }
}

PHP;
    }

    /**
     * Compile the routes and return the PHP code
     *
     * @param RouteCollection $routes
     * @return string
     */
    private function compileRoutes(RouteCollection $routes): string
    {
        $dumper = new CompiledUrlMatcherDumper($routes);
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
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        file_put_contents($cacheFile, $content);
    }
}
