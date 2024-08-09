<?php

namespace Arakne\Tests\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcherCompilerTest extends TestCase
{
    const CACHE_DIR = '/tmp/url_matcher_compiler_test';

    private RouteCollection $routes;
    private Application $app;

    protected function setUp(): void
    {
        $routesBuilder = new RouteCollectionBuilder();

        $routesBuilder->get('/hello', HelloRequest::class);
        $routesBuilder->post('/test', MixedFieldsRequest::class);

        $this->routes = $routesBuilder->routes;
        $this->app = new class extends Application {
            public function cacheDir(): string
            {
                return UrlMatcherCompilerTest::CACHE_DIR;
            }
        };

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::CACHE_DIR, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
    }

    #[Test]
    public function compile()
    {
        $routes = clone $this->routes;

        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        $this->assertEquals($routes, $this->routes);

        $this->assertFileExists(self::CACHE_DIR . '/compiled_routes.php');
        $this->assertEquals(<<<'PHP'
<?php

if (!class_exists(GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9::class)) {
    final readonly class GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9
    {
        public function __construct(
            private string $requestClassName,
        ) {
        }
    
        public function __invoke(\Psr\Http\Message\ServerRequestInterface $request): array
        {
            $extractor = match ($this->requestClassName) {
                'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\HelloRequest' => fn ($request) => $request->getQueryParams(),
                'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\MixedFieldsRequest' => fn ($request) => ['key' => $request->getQueryParams()['key'] ?? null, ] + (array) ($request->getParsedBody() ?? []),

                default => throw new \RuntimeException("No fields extractor found for class $this->requestClassName"),
            };
    
            return $extractor($request);
        }
    }
}


/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/hello' => [[['_route' => 'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\HelloRequest', '_target' => 'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\HelloRequest', '_fields_extractor' => 'GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9'], null, ['GET' => 0], null, false, false, null]],
        '/test' => [[['_route' => 'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\MixedFieldsRequest', '_target' => 'Arakne\\Tests\\Spinneret\\Router\\Fixtures\\MixedFieldsRequest', '_fields_extractor' => 'GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9'], null, ['POST' => 0], null, false, false, null]],
    ],
    [ // $regexpList
    ],
    [ // $dynamicRoutes
    ],
    null, // $checkCondition
];

PHP
, file_get_contents(self::CACHE_DIR . '/compiled_routes.php')
);
    }

    #[Test]
    public function loadNotYetCompiled()
    {
        $compiler = new UrlMatcherCompiler();
        $this->assertNull($compiler->load($this->app, new RequestContext()));
    }

    #[Test]
    public function loadCompiled()
    {
        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        $loaded = $compiler->load($this->app, new RequestContext());

        $this->assertInstanceOf(CompiledUrlMatcher::class, $loaded);
        $this->assertSame([
            "_route" => "Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest",
            "_target" => "Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest",
            "_fields_extractor" => "GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9",
        ], $loaded->match('/hello'));

        $extractor = new \GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9(HelloRequest::class);
        $psrRequest = new ServerRequest('GET', '/hello?name=John');

        $this->assertSame(['name' => 'John'], $extractor($psrRequest));
    }

    #[Test]
    public function loadCompiledInvalidFile()
    {
        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        file_put_contents(self::CACHE_DIR . '/compiled_routes.php', '<?php return 42;');

        $this->assertNull($compiler->load($this->app, new RequestContext()));
    }

    #[Test]
    public function loadCompiledError()
    {
        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        file_put_contents(self::CACHE_DIR . '/compiled_routes.php', '<?php syntax error!');

        $this->assertNull($compiler->load($this->app, new RequestContext()));
    }
}
