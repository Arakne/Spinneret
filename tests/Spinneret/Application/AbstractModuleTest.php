<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Tests\Spinneret\Application\Fixtures\Bar;
use Arakne\Tests\Spinneret\Application\Fixtures\Baz;
use Arakne\Tests\Spinneret\Application\Fixtures\Foo;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloPresenter;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRenderer;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRequest;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloResponse;
use Arakne\Tests\Spinneret\Util\Fixtures\A\B;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function Arakne\Spinneret\Application\service;

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

        $this->assertSame([HelloRequest::class => HelloPresenter::class], $module->presenters());
        $container = new ContainerBuilder();

        $module->register($container);

        $this->assertInstanceOf(HelloPresenter::class, $container->get(HelloPresenter::class));
        $this->assertTrue($container->getDefinition(HelloPresenter::class)->isPublic());
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

        $this->assertSame([HelloResponse::class => HelloRenderer::class], $module->renderers());
        $container = new ContainerBuilder();

        $module->register($container);

        $this->assertInstanceOf(HelloRenderer::class, $container->get(HelloRenderer::class));
        $this->assertTrue($container->getDefinition(HelloRenderer::class)->isPublic());
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

        $this->assertSame([HelloRequest::class => HelloPresenter::class], $module->presenters());
        $container = new ContainerBuilder();

        $module->register($container);

        $this->assertInstanceOf(HelloPresenter::class, $container->get(HelloPresenter::class));
        $this->assertTrue($container->getDefinition(HelloPresenter::class)->isPublic());

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
}
