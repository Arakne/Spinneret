<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewEngineInterface;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ViewTest extends TestCase
{
    #[Test]
    public function extends()
    {
        $view = new View(
            $this->createMock(ViewEngineInterface::class),
            new stdClass(),
        );

        $this->assertNull($view->parent());

        $view->extends($parent = new stdClass());
        $this->assertSame($parent, $view->parent());

        try {
            $view->extends(new stdClass());
            $this->fail('An exception should have been thrown');
        } catch (\LogicException $e) {
            $this->assertSame('Parent view is already set', $e->getMessage());
        }
    }

    #[Test]
    public function translateWithoutTranslator()
    {
        $view = new View(
            $this->createMock(ViewEngineInterface::class),
            new stdClass(),
        );

        $this->assertSame('With translations', $view->_('With translations'));
        $this->assertSame('Hello world!', $view->_('Hello {name}!', ['{name}' => 'world']));

        $translatable = new class() implements TranslatableInterface
        {
            #[Override] public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return $translator->trans('With translations', [], null, $locale);
            }
        };

        $this->assertSame('With translations', $view->_($translatable));
        $this->assertSame('', $view->_(null));
    }

    #[Test]
    public function translateWithTranslatorWithoutLocaleSetShouldUseDefaultLocale()
    {
        $translator = new Translator('es');
        $translator->addLoader('php', new PhpFileLoader());
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/en.php', 'en');
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/fr.php', 'fr');
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/es.php', 'es');

        $view = new View(
            $this->createMock(ViewEngineInterface::class),
            new stdClass(),
            translator: $translator,
        );

        $this->assertSame('Con traducciones', $view->_('With translations'));
        $this->assertSame('¡Hola world!', $view->_('Hello {name}!', ['{name}' => 'world']));

        $translatable = new class() implements TranslatableInterface
        {
            #[Override] public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return $translator->trans('With translations', [], null, $locale);
            }
        };

        $this->assertSame('Con traducciones', $view->_($translatable));
        $this->assertSame('', $view->_(null));
    }

    #[Test]
    public function translateWithTranslatorAndLocale()
    {
        $translator = new Translator('es');
        $translator->addLoader('php', new PhpFileLoader());
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/en.php', 'en');
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/fr.php', 'fr');
        $translator->addResource('php', __DIR__ . '/Fixtures/translations/es.php', 'es');

        $view = new View(
            $this->createMock(ViewEngineInterface::class),
            new stdClass(),
            translator: $translator,
            locale: 'fr',
        );

        $this->assertSame('Avec traductions', $view->_('With translations'));
        $this->assertSame('Bonjour world !', $view->_('Hello {name}!', ['{name}' => 'world']));

        $translatable = new class() implements TranslatableInterface
        {
            #[Override] public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return $translator->trans('With translations', [], null, $locale);
            }
        };

        $this->assertSame('Avec traductions', $view->_($translatable));
        $this->assertSame('', $view->_(null));
    }
}
