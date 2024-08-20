<?php

namespace Arakne\Tests\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Util\Files;
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
    private RouteCollection $routes;
    private Application $app;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = '/tmp/url_matcher_compiler_test';
        $routesBuilder = new RouteCollectionBuilder();

        $routesBuilder->get('/hello', HelloRequest::class);
        $routesBuilder->post('/test', MixedFieldsRequest::class);

        $this->routes = $routesBuilder->routes;
        $this->app = new class($this->cacheDir) extends Application {
            public function __construct(private string $cacheDir)
            {
                parent::__construct();
            }

            public function cacheDir(): string
            {
                return $this->cacheDir;
            }
        };

        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Files::rmdir($this->cacheDir);
    }

    #[Test]
    public function compile()
    {
        $routes = clone $this->routes;

        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        $this->assertEquals($routes, $this->routes);

        $this->assertFileExists($this->cacheDir . '/compiled_routes.php');
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
, file_get_contents($this->cacheDir . '/compiled_routes.php')
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

        file_put_contents($this->cacheDir . '/compiled_routes.php', '<?php return 42;');

        $this->assertNull($compiler->load($this->app, new RequestContext()));
    }

    #[Test]
    public function loadCompiledError()
    {
        $compiler = new UrlMatcherCompiler();
        $compiler->compile($this->app, $this->routes);

        file_put_contents($this->cacheDir . '/compiled_routes.php', '<?php syntax error!');

        $this->assertNull($compiler->load($this->app, new RequestContext()));
    }
}
