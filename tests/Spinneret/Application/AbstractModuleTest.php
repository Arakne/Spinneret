<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Arakne\Spinneret\Router\Result\NotFound;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Spinneret\View\ViewRendererInterface;
use Arakne\Tests\Spinneret\Application\Fixtures\Aggr;
use Arakne\Tests\Spinneret\Application\Fixtures\Bar;
use Arakne\Tests\Spinneret\Application\Fixtures\Baz;
use Arakne\Tests\Spinneret\Application\Fixtures\Foo;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloPresenter;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRenderer;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRequest;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloResponse;
use Arakne\Tests\Spinneret\Application\Fixtures\LazyContainer;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Arakne\Spinneret\Application\service;
use function Arakne\Spinneret\Application\service_closure;
use function Arakne\Spinneret\Application\tagged_services;

class AbstractModuleTest extends TestCase
{
    #[Test]
    public function presenters()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->presenter(HelloRequest::class, HelloPresenter::class);
            }
        };

        $container = new ContainerBuilder();
        $module->register($container);

        $this->assertTrue($container->services[HelloPresenter::class]->public);
        $this->assertEquals([new Presenter(HelloRequest::class)], $container->services[HelloPresenter::class]->tags);
        $this->assertInstanceOf(HelloPresenter::class, $container->build()->get(HelloPresenter::class));
    }

    #[Test]
    public function presentersWithMultipleRequestOnSamePresenter()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->presenter(HelloRequest::class, RequestPresenter::class);
                $this->presenter(NotFound::class, RequestPresenter::class);
            }
        };

        $container = new ContainerBuilder();
        $module->register($container);

        $this->assertInstanceOf(RequestPresenter::class, $container->build()->get(RequestPresenter::class));
        $this->assertTrue($container->services[RequestPresenter::class]->public);
        $this->assertEquals([
            new Presenter(HelloRequest::class),
            new Presenter(NotFound::class),
        ], $container->services[RequestPresenter::class]->tags);
    }

    #[Test]
    public function renderers()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->renderer(HelloResponse::class, HelloRenderer::class);
            }
        };

        $container = new ContainerBuilder();
        $module->register($container);

        $this->assertInstanceOf(HelloRenderer::class, $container->build()->get(HelloRenderer::class));
        $this->assertTrue($container->services[HelloRenderer::class]->public);
        $this->assertEquals([new Renderer(HelloResponse::class)], $container->services[HelloRenderer::class]->tags);
    }

    #[Test]
    public function route()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->get('/foo', HelloRequest::class, HelloPresenter::class);
            }
        };

        $container = new ContainerBuilder();
        $module->register($container);

        $this->assertInstanceOf(HelloPresenter::class, $container->build()->get(HelloPresenter::class));
        $this->assertTrue($container->services[HelloPresenter::class]->public);
        $this->assertEquals([new Presenter(HelloRequest::class)], $container->services[HelloPresenter::class]->tags);

        $routes = new RouteCollectionBuilder();
        $module->configureRoutes($routes);

        $this->assertEquals([
            '_target' => HelloRequest::class,
            '_fields_extractor' => FieldsExtractor::class,
        ], $routes->routes->get(HelloRequest::class)->getDefaults());
    }

    #[Test]
    public function service()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello']);
                $this->service(Bar::class, autowire: true, public: true);
                $this->service(Baz::class, tags: ['test', (object) ['key' => 'value']]);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);

        $this->assertInstanceOf(Foo::class, $container->build()->get(Foo::class));
        $this->assertFalse($container->services[Foo::class]->public);
        $this->assertSame('Hello', $container->build()->get(Foo::class)->bar);

        $this->assertEquals(['test', (object) ['key' => 'value']], $container->services[Baz::class]->tags);

        $this->assertInstanceOf(Bar::class, $container->build()->get(Bar::class));
        $this->assertTrue($container->services[Bar::class]->public);
    }

    #[Test]
    public function autowire()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello']);
                $this->autowire(Bar::class);
                $this->autowire(Baz::class, tags: ['test', (object) ['key' => 'value']]);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);
        $this->assertFalse($container->services[Bar::class]->public);
        $this->assertEquals(['test', (object) ['key' => 'value']], $container->services[Baz::class]->tags);
    }

    #[Test]
    public function serviceManualArguments()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello']);
                $this->service(Bar::class, [service(Foo::class)]);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);
        $built = $container->build();
        $this->assertSame($built->get(Foo::class), $built->get(Bar::class)->foo);
    }

    #[Test]
    public function alias()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello'], aliases: ['alias1']);
                $this->alias('alias2', Foo::class);
                $this->autowire(Baz::class, aliases: ['alias3']);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);

        $built = $container->build();

        $this->assertInstanceOf(Foo::class, $built->get('alias1'));
        $this->assertInstanceOf(Baz::class, $built->get('alias3'));
        $this->assertSame($built->get(Foo::class), $built->get('alias1'));
        $this->assertSame($built->get('alias1'), $built->get('alias2'));
        $this->assertSame($built->get(Baz::class), $built->get('alias3'));
    }

    #[Test]
    public function tagged_services()
    {
        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello'], public: true, tags: ['a']);
                $this->service(Baz::class, public: true, tags: ['a']);
                $this->service(Aggr::class, parameters: [tagged_services('a')], public: true);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);
        $built = $container->build();

        $this->assertInstanceOf(Aggr::class, $built->get(Aggr::class));
        $this->assertSame([
            $built->get(Foo::class),
            $built->get(Baz::class),
        ], $built->get(Aggr::class)->services);
    }

    #[Test]
    public function service_closure()
    {

        $module = new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->service(Foo::class, ['Hello'], public: true);
                $this->service(LazyContainer::class, parameters: [service_closure(Foo::class)], public: true);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);
        $built = $container->build();

        $this->assertInstanceOf(LazyContainer::class, $built->get(LazyContainer::class));
        $this->assertSame($built->get(Foo::class), ($built->get(LazyContainer::class)->ref)());
    }
}
