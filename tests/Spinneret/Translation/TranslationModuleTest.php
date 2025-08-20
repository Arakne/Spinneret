<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Translation\CollectorTranslator;
use Arakne\Spinneret\Translation\TranslationConfig;
use Arakne\Spinneret\Translation\TranslationModule;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\PseudoLocalizationTranslator;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationModuleTest extends TestCase
{
    #[Test]
    public function register()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder(registerAsPublic: true);

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
        );

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

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
        $container = new ContainerBuilder(registerAsPublic: true);

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
            collectTranslations: true,
        );

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

        $this->assertInstanceOf(CollectorTranslator::class, $container->get(TranslatorInterface::class));
        $this->assertInstanceOf(Translator::class, $container->get(Translator::class));
        $this->assertInstanceOf(CollectorTranslator::class, $container->get(CollectorTranslator::class));
        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(PseudoLocalizationTranslator::class));
    }

    #[Test]
    public function registerWithPseudoLocalization()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder(registerAsPublic: true);

        $config = TranslationConfig::default($app)->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
            pseudoLocalization: true,
        );

        $module = TranslationModule::create($app)->withConfiguration($config);
        $module->register($container);
        $container = $container->build();

        $container->set(Application::class, $app);
        $container->set(TranslationConfig::class, $config);

        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(TranslatorInterface::class));
        $this->assertInstanceOf(Translator::class, $container->get(Translator::class));
        $this->assertInstanceOf(CollectorTranslator::class, $container->get(CollectorTranslator::class));
        $this->assertInstanceOf(PseudoLocalizationTranslator::class, $container->get(PseudoLocalizationTranslator::class));
    }
}
