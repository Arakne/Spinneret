<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Arakne\Spinneret\Router\Result\NotFound;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
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
use Arakne\Tests\Spinneret\Util\Fixtures\A\B;
use ArrayObject;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function Arakne\Spinneret\Application\service;
use function Arakne\Spinneret\Application\service_closure;
use function Arakne\Spinneret\Application\tagged_services;
use function var_dump;

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

        $this->assertInstanceOf(HelloPresenter::class, $container->get(HelloPresenter::class));
        $this->assertTrue($container->getDefinition(HelloPresenter::class)->isPublic());
        $this->assertSame([PresenterInterface::class => [['request' => HelloRequest::class]]], $container->getDefinition(HelloPresenter::class)->getTags());
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

        $this->assertInstanceOf(RequestPresenter::class, $container->get(RequestPresenter::class));
        $this->assertTrue($container->getDefinition(RequestPresenter::class)->isPublic());
        $this->assertSame([PresenterInterface::class => [
            ['request' => HelloRequest::class],
            ['request' => NotFound::class],
        ]], $container->getDefinition(RequestPresenter::class)->getTags());
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

        $this->assertInstanceOf(HelloRenderer::class, $container->get(HelloRenderer::class));
        $this->assertTrue($container->getDefinition(HelloRenderer::class)->isPublic());
        $this->assertSame([ViewRendererInterface::class => [['response' => HelloResponse::class]]], $container->getDefinition(HelloRenderer::class)->getTags());
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

        $this->assertInstanceOf(HelloPresenter::class, $container->get(HelloPresenter::class));
        $this->assertTrue($container->getDefinition(HelloPresenter::class)->isPublic());
        $this->assertSame([PresenterInterface::class => [['request' => HelloRequest::class]]], $container->getDefinition(HelloPresenter::class)->getTags());

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
                $this->service(Baz::class, tags: ['test', 'other' => ['key' => 'value']]);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);

        $this->assertInstanceOf(Foo::class, $container->get(Foo::class));
        $this->assertFalse($container->getDefinition(Foo::class)->isPublic());
        $this->assertFalse($container->getDefinition(Foo::class)->isAutowired());
        $this->assertSame('Hello', $container->get(Foo::class)->bar);

        $this->assertSame([
            'test' => [[]],
            'other' => [['key' => 'value']],
        ], $container->getDefinition(Baz::class)->getTags());

        $container->compile();

        $this->assertInstanceOf(Bar::class, $container->get(Bar::class));
        $this->assertTrue($container->getDefinition(Bar::class)->isPublic());
        $this->assertTrue($container->getDefinition(Bar::class)->isAutowired());
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
                $this->autowire(Baz::class, tags: ['test', 'other' => ['key' => 'value']]);
            }
        };

        $container = new ContainerBuilder();

        $module->register($container);
        $this->assertFalse($container->getDefinition(Bar::class)->isPublic());
        $this->assertTrue($container->getDefinition(Bar::class)->isAutowired());
        $this->assertSame([
            'test' => [[]],
            'other' => [['key' => 'value']],
        ], $container->getDefinition(Baz::class)->getTags());
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
        $this->assertSame($container->get(Foo::class), $container->get(Bar::class)->foo);
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

        $this->assertInstanceOf(Foo::class, $container->get('alias1'));
        $this->assertInstanceOf(Baz::class, $container->get('alias3'));
        $this->assertSame($container->get(Foo::class), $container->get('alias1'));
        $this->assertSame($container->get('alias1'), $container->get('alias2'));
        $this->assertSame($container->get(Baz::class), $container->get('alias3'));
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
        $container->compile();

        $this->assertInstanceOf(Aggr::class, $container->get(Aggr::class));
        $this->assertSame([
            $container->get(Foo::class),
            $container->get(Baz::class),
        ], $container->get(Aggr::class)->services);
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
        $container->compile();

        $this->assertInstanceOf(LazyContainer::class, $container->get(LazyContainer::class));
        $this->assertSame($container->get(Foo::class), ($container->get(LazyContainer::class)->ref)());
    }
}
