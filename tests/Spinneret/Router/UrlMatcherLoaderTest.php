<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteCollectionLoaderInterface;
use Arakne\Spinneret\Router\UrlMatcherLoader;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcherLoaderTest extends TestCase
{
    public string $cacheDir;

    private RouteCollectionLoaderInterface $routesLoader;

    protected function setUp(): void
    {
        $this->cacheDir  = '/tmp/url_matcher_loader_test';
        $this->routesLoader = new class implements RouteCollectionLoaderInterface {
            public function load(Application $application): RouteCollection
            {
                $builder = new RouteCollectionBuilder();

                $builder->get('/hello', HelloRequest::class);
                $builder->post('/test', MixedFieldsRequest::class);

                return $builder->routes;
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
    public function loadDevModeShouldNotLoadCompiledRoutes()
    {
        $app = $this->createApp(true);
        $loader = new UrlMatcherLoader($this->routesLoader, new RequestContext(), new UrlMatcherCompiler());

        $matcher = $loader->load($app);

        $this->assertInstanceOf(UrlMatcher::class, $matcher);
        $this->assertNotInstanceOf(CompiledUrlMatcher::class, $matcher);

        $this->assertEquals($matcher, $loader->load($app));
        $this->assertNotSame($matcher, $loader->load($app));

        $this->assertEquals([
            '_target' => HelloRequest::class,
            '_fields_extractor' => FieldsExtractor::class,
            '_route' => HelloRequest::class,
        ], $matcher->match('/hello'));

        $this->assertFileExists($this->cacheDir . '/compiled_routes.php');
    }

    #[Test]
    public function loadNotDevModeShouldNotLoadCompiledRoutesIfPresent()
    {
        $app = $this->createApp(false);
        $loader = new UrlMatcherLoader($this->routesLoader, new RequestContext(), new UrlMatcherCompiler());

        $matcher = $loader->load($app);
        $this->assertFileExists($this->cacheDir . '/compiled_routes.php');

        $this->assertInstanceOf(UrlMatcher::class, $matcher);
        $this->assertNotInstanceOf(CompiledUrlMatcher::class, $matcher);

        $this->assertEquals([
            '_target' => HelloRequest::class,
            '_fields_extractor' => FieldsExtractor::class,
            '_route' => HelloRequest::class,
        ], $matcher->match('/hello'));

        $compiledMatcher = $loader->load($app);

        $this->assertInstanceOf(CompiledUrlMatcher::class, $compiledMatcher);
        $this->assertEquals([
            '_target' => HelloRequest::class,
            '_fields_extractor' => 'GeneratedFieldsExtractorb5cfbd607dd560381e9716a94549f3e9',
            '_route' => HelloRequest::class,
        ], $compiledMatcher->match('/hello'));
    }

    #[Test]
    public function loadNotDevModeWithoutCompilerShouldNotCompileNorLoadCompiled()
    {
        $app = $this->createApp(false);
        $loader = new UrlMatcherLoader($this->routesLoader, new RequestContext(), null);

        $matcher = $loader->load($app);
        $this->assertFileDoesNotExist($this->cacheDir . '/compiled_routes.php');

        $this->assertInstanceOf(UrlMatcher::class, $matcher);
        $this->assertNotInstanceOf(CompiledUrlMatcher::class, $matcher);

        $this->assertEquals($matcher, $loader->load($app));
        $this->assertNotSame($matcher, $loader->load($app));

        $this->assertEquals([
            '_target' => HelloRequest::class,
            '_fields_extractor' => FieldsExtractor::class,
            '_route' => HelloRequest::class,
        ], $matcher->match('/hello'));
    }

    private function createApp(bool $dev): Application
    {
        try {
            return new class($this->cacheDir, $dev) extends Application {
                public function __construct(
                    private readonly string $cacheDir,
                    bool $isDev
                ) {
                    parent::__construct($isDev, 'test');
                }

                public function cacheDir(): string
                {
                    return $this->cacheDir;
                }
            };
        } finally {
            $this->clearCache();
        }
    }
}
