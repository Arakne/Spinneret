<?php

namespace Arakne\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ConfigurableModuleInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Locale;
use Override;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\PseudoLocalizationTranslator;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_first;
use function class_exists;

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
 * - {@see TranslationConfig}
 *
 * @implements ConfigurableModuleInterface<TranslationConfig>
 */
final readonly class TranslationModule implements ConfigurableModuleInterface
{
    public function __construct(
        private TranslationConfig $config
    ) {}

    #[Override]
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(Translator::class)
            ->factory(self::createTranslator(...))
            ->arg(new Reference(TranslationConfig::class))
        ;

        $containerBuilder->register(CollectorTranslator::class, [
            new Reference(Translator::class),
            $this->config->collectedLocales ?? $this->config->availableLocales,
            $this->config->collectorOutputFile ?? $this->config->translationDir . '/{locale}.missing.php',
        ]);

        $containerBuilder->register(PseudoLocalizationTranslator::class, [
            new Reference(Translator::class),
            [
                'expansion_factor' => $this->config->pseudoLocalizationExpansionFactor,
                'accents' => $this->config->pseudoLocalizationAccents,
                'brackets' => $this->config->pseudoLocalizationBrackets,
            ],
        ]);

        if ($this->config->collectTranslations) {
            $containerBuilder->alias(TranslatorInterface::class, CollectorTranslator::class);
        } elseif ($this->config->pseudoLocalization) {
            $containerBuilder->alias(TranslatorInterface::class, PseudoLocalizationTranslator::class);
        } else {
            $containerBuilder->alias(TranslatorInterface::class, Translator::class);
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
    public function configureRoutes(RouteCollectionBuilder $builder): void
    {
        // No-op
    }

    public static function createTranslator(TranslationConfig $config): Translator
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

        foreach ($config->availableLocales as $locale) {
            $translator->addResource('php', $config->translationDir . DIRECTORY_SEPARATOR . $locale . '.php', $locale);
        }

        return $translator;
    }

    /**
     * Create the translation module with default configuration.
     *
     * @param Application $app
     * @return self
     */
    public static function create(Application $app): self
    {
        return new self(TranslationConfig::default($app));
    }
}
