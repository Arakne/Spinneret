<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewThemeResolverInterface;
use Arakne\Tests\Spinneret\View\Fixtures\EmbeddedComponent;
use Arakne\Tests\Spinneret\View\Fixtures\EmbeddedComponentRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\Layout;
use Arakne\Tests\Spinneret\View\Fixtures\LayoutRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\OnlyResponseConfigurator;
use Arakne\Tests\Spinneret\View\Fixtures\OtherResponse;
use Arakne\Tests\Spinneret\View\Fixtures\RendererWithResponseConfigurator;
use Arakne\Tests\Spinneret\View\Fixtures\ResponseWithoutBody;
use Arakne\Tests\Spinneret\View\Fixtures\ResponseWithParent;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleRendererAlternative;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleResponse;
use Arakne\Tests\Spinneret\View\Fixtures\WithEmbedded;
use Arakne\Tests\Spinneret\View\Fixtures\WithEmbeddedRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\WithParentRenderer;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use stdClass;

use function ob_start;

class EngineWithThemesTest extends TestCase
{
    private Engine $engine;

    protected function setUp(): void
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set(SimpleRenderer::class, new SimpleRenderer());
        $container->set(RendererWithResponseConfigurator::class, new RendererWithResponseConfigurator());
        $container->set(WithParentRenderer::class, new WithParentRenderer());
        $container->set(LayoutRenderer::class, new LayoutRenderer());
        $container->set(OnlyResponseConfigurator::class, new OnlyResponseConfigurator());
        $container->set(EmbeddedComponentRenderer::class, new EmbeddedComponentRenderer());
        $container->set(WithEmbeddedRenderer::class, new WithEmbeddedRenderer());
        $container->set(SimpleRendererAlternative::class, new SimpleRendererAlternative());

        $container = $container->build();

        $this->engine = new Engine(
            $container,
            new Psr17Factory(),
            new Psr17Factory(),
            null,
            null,
            new class implements ViewThemeResolverInterface {
                #[Override]
                public function resolveThemeId(object $data, ?ServerRequestInterface $request): ?string
                {
                    return $request->getAttribute('theme') ?? null;
                }
            },
            renderers: [
                SimpleResponse::class => SimpleRenderer::class,
                OtherResponse::class => RendererWithResponseConfigurator::class,
                ResponseWithParent::class => WithParentRenderer::class,
                Layout::class => LayoutRenderer::class,
                ResponseWithoutBody::class => OnlyResponseConfigurator::class,
                WithEmbedded::class => WithEmbeddedRenderer::class,
                EmbeddedComponent::class => EmbeddedComponentRenderer::class,
            ],
            themeRenderers: [
                'other' => [
                    SimpleResponse::class => SimpleRendererAlternative::class,
                ]
            ],
        );
    }

    #[Test]
    public function responseSimpleRenderer()
    {
        $response = $this->engine->response(
            $r = new SimpleResponse('Hello, world!'),
            new ServerRequest('GET', '/')
        );

        $this->assertSame('<p>Hello, world!</p>', (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function responseUndefinedTheme()
    {
        $response = $this->engine->response(
            $r = new SimpleResponse('Hello, world!'),
            new ServerRequest('GET', '/')->withAttribute('theme', 'undefined')
        );

        $this->assertSame('<p>Hello, world!</p>', (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function responseCustomTheme()
    {
        $response = $this->engine->response(
            $r = new SimpleResponse('Hello, world!'),
            new ServerRequest('GET', '/')->withAttribute('theme', 'other')
        );

        $this->assertSame('<blockquote>Hello, world!</blockquote>', (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function displayWithTheme()
    {
        $view = new View(
            $this->engine,
            new stdClass(),
            theme: 'other'
        );

        ob_start();
        $this->engine->display($r = new SimpleResponse('Hello, world!'), $view);
        $content = ob_get_clean();

        $this->assertSame('<blockquote>Hello, world!</blockquote>', $content);
    }

    #[Test]
    public function renderWithTheme()
    {
        $view = new View(
            $this->engine,
            new stdClass(),
            theme: 'other'
        );

        $content = $this->engine->render($r = new SimpleResponse('Hello, world!'), $view);

        $this->assertSame('<blockquote>Hello, world!</blockquote>', $content);
    }
}
