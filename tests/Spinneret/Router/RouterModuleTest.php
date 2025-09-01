<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\FormModule;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompiler;
use Arakne\Spinneret\Router\Compiler\UrlGeneratorCompilerInterface;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompiler;
use Arakne\Spinneret\Router\Compiler\UrlMatcherCompilerInterface;
use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\RouteCollectionLoader;
use Arakne\Spinneret\Router\RouteCollectionLoaderInterface;
use Arakne\Spinneret\Router\Router;
use Arakne\Spinneret\Router\RouterConfig;
use Arakne\Spinneret\Router\RouterInterface;
use Arakne\Spinneret\Router\RouterModule;
use Arakne\Spinneret\Router\UrlGeneratorLoader;
use Arakne\Spinneret\Router\UrlGeneratorLoaderInterface;
use Arakne\Spinneret\Router\UrlMatcherLoader;
use Arakne\Spinneret\Router\UrlMatcherLoaderInterface;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Router\Fixtures\GetRequestWithAttribute;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedRequestWithAttribute;
use Arakne\Tests\Spinneret\Router\Fixtures\PostRequestWithAttribute;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\RequestContext;

use function clearstatcache;

class RouterModuleTest extends TestCase
{
    protected function tearDown(): void
    {
        Files::rmdir(__DIR__.'/../../../var/cache/test-router');
        clearstatcache();
    }

    #[Test]
    public function register()
    {
        $app = new Application(env: 'test-router');
        $container = new ContainerBuilder();

        $routerModule = new RouterModule();
        $routerModule->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(FormFactoryInterface::class, DefaultFormFactory::runtime());
        $container->set(RouterConfig::class, new RouterConfig());

        $this->assertInstanceOf(Router::class, $container->get(RouterInterface::class));
        $this->assertInstanceOf(UrlMatcherLoader::class, $container->get(UrlMatcherLoaderInterface::class));
        $this->assertInstanceOf(UrlMatcherCompiler::class, $container->get(UrlMatcherCompilerInterface::class));
        $this->assertInstanceOf(UrlGeneratorLoader::class, $container->get(UrlGeneratorLoader::class));
        $this->assertInstanceOf(UrlGeneratorLoader::class, $container->get(UrlGeneratorLoaderInterface::class));
        $this->assertInstanceOf(UrlGeneratorCompiler::class, $container->get(UrlGeneratorCompilerInterface::class));
        $this->assertInstanceOf(RouteCollectionLoader::class, $container->get(RouteCollectionLoaderInterface::class));
        $this->assertEquals(new RequestContext(), $container->get(RequestContext::class));
    }

    #[Test]
    public function registerWithConfig()
    {
        $app = new class(true, env: 'test-router') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }
        };
        $container = new ContainerBuilder();
        $routerModule = new RouterModule();
        $routerModule->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(FormFactoryInterface::class, DefaultFormFactory::runtime());
        $container->set(RouterConfig::class, $app->config()[RouterConfig::class]);

        $this->assertInstanceOf(Router::class, $container->get(RouterInterface::class));
        $this->assertInstanceOf(UrlMatcherLoader::class, $container->get(UrlMatcherLoaderInterface::class));
        $this->assertInstanceOf(UrlMatcherCompiler::class, $container->get(UrlMatcherCompilerInterface::class));
        $this->assertInstanceOf(RouteCollectionLoader::class, $container->get(RouteCollectionLoaderInterface::class));
        $this->assertEquals(RequestContext::fromUri('http://foo.example.com/bar'), $container->get(RequestContext::class));
    }

    #[Test]
    public function registerWithRouteAttributes()
    {
        $app = new class(true, env: 'test-router') extends Application {
            public function configDir(): string
            {
                return __DIR__.'/Fixtures/config';
            }
        };
        $container = new ContainerBuilder();

        new FormModule()->register($container);

        $container->register(GetRequestWithAttribute::class);
        $container->register(MixedRequestWithAttribute::class);
        $container->register(PostRequestWithAttribute::class);

        $routerModule = new RouterModule();
        $routerModule->register($container);

        $container = $container->build();
        $container->set(Application::class, $app);
        $container->set(RouterConfig::class, new RouterConfig());

        $router = $container->get(RouterInterface::class);
        $this->assertInstanceOf(GetRequestWithAttribute::class, $router->request(new ServerRequest('GET', '/get-request-with-attribute'))->routedRequest);
        $this->assertInstanceOf(MethodNotAllowed::class, $router->request(new ServerRequest('POST', '/get-request-with-attribute'))->routedRequest);
        $this->assertInstanceOf(PostRequestWithAttribute::class, $router->request(new ServerRequest('POST', '/post-request-with-attribute'))->routedRequest);
        $this->assertInstanceOf(MethodNotAllowed::class, $router->request(new ServerRequest('GET', '/post-request-with-attribute'))->routedRequest);
        $this->assertInstanceOf(MixedRequestWithAttribute::class, $router->request(new ServerRequest('POST', '/mixed-request-with-attribute'))->routedRequest);
    }
}
