<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\Engine;
use Arakne\Tests\Spinneret\View\Fixtures\Layout;
use Arakne\Tests\Spinneret\View\Fixtures\LayoutRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\OtherResponse;
use Arakne\Tests\Spinneret\View\Fixtures\RendererWithResponseConfigurator;
use Arakne\Tests\Spinneret\View\Fixtures\ResponseWithParent;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleResponse;
use Arakne\Tests\Spinneret\View\Fixtures\WithParentRenderer;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EngineTest extends TestCase
{
    private Engine $engine;
    private SimpleRenderer $renderer;

    protected function setUp(): void
    {
        $container = new ContainerBuilder();
        $container->set(SimpleRenderer::class, $this->renderer = new SimpleRenderer());
        $container->set(RendererWithResponseConfigurator::class, new RendererWithResponseConfigurator());
        $container->set(WithParentRenderer::class, new WithParentRenderer());
        $container->set(LayoutRenderer::class, new LayoutRenderer());

        $this->engine = new Engine(
            $container,
            new Psr17Factory(),
            new Psr17Factory(),
            [
                SimpleResponse::class => SimpleRenderer::class,
                OtherResponse::class => RendererWithResponseConfigurator::class,
                ResponseWithParent::class => WithParentRenderer::class,
                Layout::class => LayoutRenderer::class,
            ]
        );
    }

    #[Test]
    public function responseSimpleRenderer()
    {
        $response = $this->engine->response($r = new SimpleResponse('Hello, world!'));

        $this->assertSame('<p>Hello, world!</p>', (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame($r, $this->renderer->data);
        $this->assertSame($r, $this->renderer->view->data);
    }

    #[Test]
    public function renderSimple()
    {
        $response = $this->engine->render(new SimpleResponse('Hello, world!'));
        $this->assertSame('<p>Hello, world!</p>', $response);
    }

    #[Test]
    public function responseWithResponseConfigurator()
    {
        $response = $this->engine->response(new OtherResponse('Hello, world!'));

        $this->assertSame('<p>Hello, world!</p>', (string) $response->getBody());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('test', $response->getHeaderLine('X-Test'));
    }

    #[Test]
    public function responseRendererNotRegister()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No renderer found for stdClass');

        $this->engine->response(new stdClass());
    }

    #[Test]
    public function responseWithLayout()
    {
        $response = $this->engine->response($r = new ResponseWithParent('Hello, world!'));

        $this->assertSame(<<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>My page</title>
            </head>
            <body>
                <p>Hello, world!</p>            </body>
        </html>
        
HTML
, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }
}
