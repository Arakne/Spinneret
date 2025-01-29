<?php

namespace Arakne\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Locale;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\PseudoLocalizationTranslator;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_first;
use function class_exists;
use function str_replace;

/**
 * Provide translation services.
 *
 * Translations are loaded from PHP array files in the configured directory.
 *
 * Provided services:
 * - {@see Translator} - The main translator service.
 * - {@see CollectorTranslator} - A translator that collects missing translations.
 * - {@see PseudoLocalizationTranslator} - A translator that pseudo-localizes translations.
 * - {@see TranslatorInterface} - Alias one of the above services based on configuration.
 *
 * Required services:
 * - {@see Application}
 * - {@see TranslationConfig}
 *
 * @implements ConfigurableModuleInterface<TranslationConfig>
 */
final readonly class TranslationModule implements ConfigurableModuleInterface
{
    public function __construct(
        private TranslationConfig $config = new TranslationConfig(),
    ) {}

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(Translator::class, Translator::class)
            ->setFactory([self::class, 'createTranslator'])
            ->setArguments([
                new Reference(TranslationConfig::class),
                new Reference(Application::class),
            ])
        ;

        $containerBuilder->register(CollectorTranslator::class, CollectorTranslator::class)
            ->setArguments([
                new Reference(Translator::class),
                $this->config->collectedLocales ?? $this->config->availableLocales,
                $this->config->collectorOutputFile ?? $this->config->translationDir . '/{locale}.missing.php',
            ])
        ;

        $containerBuilder->register(PseudoLocalizationTranslator::class, PseudoLocalizationTranslator::class)
            ->setArguments([
                new Reference(Translator::class),
                [
                    'expansion_factor' => $this->config->pseudoLocalizationExpansionFactor,
                    'accents' => $this->config->pseudoLocalizationAccents,
                    'brackets' => $this->config->pseudoLocalizationBrackets,
                ],
            ])
        ;

        if ($this->config->collectTranslations) {
            $containerBuilder->setAlias(TranslatorInterface::class, CollectorTranslator::class);
        } elseif ($this->config->pseudoLocalization) {
            $containerBuilder->setAlias(TranslatorInterface::class, PseudoLocalizationTranslator::class);
        } else {
            $containerBuilder->setAlias(TranslatorInterface::class, Translator::class);
        }
    }

    #[Override]
    public function withConfiguration(object $configuration): static
    {
        return new self($configuration);
    }

    #[Override]
    public function configuration(): object
    {
        return $this->config;
    }

    #[Override]
    public function presenters(): array
    {
        return [];
    }

    #[Override]
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    #[Override]
    public function renderers(): array
    {
        return [];
    }

    public static function createTranslator(TranslationConfig $config, Application $application): Translator
    {
        $defaultLocale = $config->defaultLocale;

        if ($defaultLocale === null) {
            if (class_exists(Locale::class)) {
                /** @var string $defaultLocale */
                $defaultLocale = Locale::getDefault();
            } else {
                $key = array_key_first($config->availableLocales);

                $defaultLocale = $key === null ? 'en' : $config->availableLocales[$key];
            }
        }

        $translator = new Translator($defaultLocale);

        $translator->addLoader('php', new PhpFileLoader());
        $directory = str_replace('%app.project_dir%', $application->projectDir(), $config->translationDir);

        foreach ($config->availableLocales as $locale) {
            $translator->addResource('php', $directory . DIRECTORY_SEPARATOR . $locale . '.php', $locale);
        }

        return $translator;
    }
}
