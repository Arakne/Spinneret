<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\ViewLocaleResolverInterface;
use Arakne\Tests\Spinneret\View\Fixtures\SimpleRenderer;
use Arakne\Tests\Spinneret\View\Fixtures\WithTranslation;
use Arakne\Tests\Spinneret\View\Fixtures\WithTranslationRenderer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

use function ob_get_clean;
use function ob_start;

class EngineWithTranslationTest extends TestCase
{
    private Engine $engine;
    private $localeResolver;

    protected function setUp(): void
    {
        $container = new ContainerBuilder();
        $container->set(WithTranslationRenderer::class, new WithTranslationRenderer());

        $translator = new Translator('en');
        $translator->addLoader('php', new PhpFileLoader());
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/en.php', 'en');
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/fr.php', 'fr');

        $this->engine = new Engine(
            $container,
            new Psr17Factory(),
            new Psr17Factory(),
            $translator,
            $this->localeResolver = new class() implements ViewLocaleResolverInterface
            {
                public string $locale = 'en';

                #[Override] public function resolve(object $data, ?ServerRequestInterface $request): ?string
                {
                    return $request?->getAttribute('locale') ?? $this->locale;
                }
            },
            renderers: [
                WithTranslation::class => WithTranslationRenderer::class,
            ]
        );
    }

    #[Test]
    public function render()
    {
        $content = $this->engine->render(new WithTranslation());

        $this->assertSame(<<<'HTML'
                    <h1>With translations</h1>

                    <p>This page is translated</p>
                    <div>Author: John Doe</div>
                    
            HTML,
            $content
        );

        $this->localeResolver->locale = 'fr';
        $content = $this->engine->render(new WithTranslation());

        $this->assertSame(<<<'HTML'
                    <h1>Avec traductions</h1>

                    <p>Cette page est traduite</p>
                    <div>Auteur : John Doe</div>
                    
            HTML,
            $content
        );
    }

    #[Test]
    public function display()
    {
        ob_start();
        $this->engine->display(new WithTranslation());
        $content = ob_get_clean();

        $this->assertSame(<<<'HTML'
                    <h1>With translations</h1>

                    <p>This page is translated</p>
                    <div>Author: John Doe</div>
                    
            HTML,
            $content
        );

        $this->localeResolver->locale = 'fr';
        ob_start();
        $this->engine->display(new WithTranslation());
        $content = ob_get_clean();

        $this->assertSame(<<<'HTML'
                    <h1>Avec traductions</h1>

                    <p>Cette page est traduite</p>
                    <div>Auteur : John Doe</div>
                    
            HTML,
            $content
        );
    }

    #[Test]
    public function response()
    {
        $req = new ServerRequest('GET', '/');
        $res = $this->engine->response(new WithTranslation(), $req);

        $this->assertSame(<<<'HTML'
                    <h1>With translations</h1>

                    <p>This page is translated</p>
                    <div>Author: John Doe</div>
                    
            HTML,
            (string) $res->getBody()
        );

        $res = $this->engine->response(new WithTranslation(), $req->withAttribute('locale', 'fr'));
        $this->assertSame(<<<'HTML'
                    <h1>Avec traductions</h1>

                    <p>Cette page est traduite</p>
                    <div>Auteur : John Doe</div>
                    
            HTML,
            (string) $res->getBody()
        );
    }
}
