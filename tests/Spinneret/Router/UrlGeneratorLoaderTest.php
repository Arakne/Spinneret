<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompiler;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteCollectionLoaderInterface;
use Arakne\Spinneret\Router\UrlGenerator;
use Arakne\Spinneret\Router\UrlGeneratorLoader;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGeneratorLoaderTest extends TestCase
{
    public string $cacheDir;

    private RouteCollectionLoaderInterface $routesLoader;

    protected function setUp(): void
    {
        $this->cacheDir  = '/tmp/url_generator_loader_test';
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
        $loader = new UrlGeneratorLoader($this->routesLoader, new RequestContext(), new UrlGeneratorCompiler());

        $generator = $loader->load($app);

        $this->assertInstanceOf(UrlGenerator::class, $generator);
        // @todo check not compiled

        $this->assertEquals($generator, $loader->load($app));
        $this->assertNotSame($generator, $loader->load($app));

        $this->assertEquals('http://localhost/hello', $generator->url(HelloRequest::class));
        $this->assertFileExists($this->cacheDir . '/url_generator_routes.php');
    }

    #[Test]
    public function loadWithoutCompiler()
    {
        $app = $this->createApp(true);
        $loader = new UrlGeneratorLoader($this->routesLoader, new RequestContext(), null);

        $generator = $loader->load($app);

        $this->assertInstanceOf(UrlGenerator::class, $generator);

        $this->assertEquals($generator, $loader->load($app));
        $this->assertNotSame($generator, $loader->load($app));

        $this->assertEquals('http://localhost/hello', $generator->url(HelloRequest::class));
        $this->assertFileDoesNotExist($this->cacheDir . '/url_generator_routes.php');
    }

    #[Test]
    public function loadNotDevModeShouldNotLoadCompiledRoutesIfPresent()
    {
        $app = $this->createApp(false);
        $loader = new UrlGeneratorLoader($this->routesLoader, new RequestContext(), new UrlGeneratorCompiler());

        $generator = $loader->load($app);
        $this->assertFileExists($this->cacheDir . '/url_generator_routes.php');

        $this->assertInstanceOf(UrlGenerator::class, $generator);
        // @todo check not compiled

        $this->assertEquals('http://localhost/hello', $generator->url(HelloRequest::class));

        $compiledGenerator = $loader->load($app);

        $this->assertInstanceOf(UrlGenerator::class, $compiledGenerator);
        // @todo check compiled

        $this->assertEquals('http://localhost/hello', $compiledGenerator->url(HelloRequest::class));
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
