<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Translation\CollectorTranslator;
use Arakne\Spinneret\Translation\TranslationConfig;
use Arakne\Spinneret\Translation\TranslationModule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\PseudoLocalizationTranslator;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $module = TranslationModule::create(new Application());
        $routes = new RouteCollectionBuilder();
        $module->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function register()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
        );

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);

        $this->assertInstanceOf(Translator::class, $container->get(TranslatorInterface::class));
        $this->assertInstanceOf(Translator::class, $container->get(Translator::class));
        $this->assertInstanceOf(CollectorTranslator::class, $container->get(CollectorTranslator::class));
        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(PseudoLocalizationTranslator::class));
        $this->assertSame('en', $container->get(Translator::class)->getLocale());
        $this->assertSame('en', $container->get(CollectorTranslator::class)->getLocale());
    }

    #[Test]
    public function registerWithCollector()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
            collectTranslations: true,
        );

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);

        $this->assertInstanceOf(CollectorTranslator::class, $container->get(TranslatorInterface::class));
        $this->assertInstanceOf(Translator::class, $container->get(Translator::class));
        $this->assertInstanceOf(CollectorTranslator::class, $container->get(CollectorTranslator::class));
        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(PseudoLocalizationTranslator::class));
    }

    #[Test]
    public function registerWithPseudoLocalization()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
            pseudoLocalization: true,
        );

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);

        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(TranslatorInterface::class));
        $this->assertInstanceOf(Translator::class, $container->get(Translator::class));
        $this->assertInstanceOf(CollectorTranslator::class, $container->get(CollectorTranslator::class));
        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(PseudoLocalizationTranslator::class));
    }
}
