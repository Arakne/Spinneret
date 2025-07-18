<?php

namespace Arakne\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;

use function array_combine;

final readonly class TranslationConfig
{
    /**
     * The available locales for the application.
     * The key and value should be the same.
     *
     * @var array<string, string>
     */
    public array $availableLocales;

    /**
     * @param array<string> $availableLocales
     */
    public function __construct(
        /**
         * The directory where translation files are stored.
         *
         * The translations must be stored as PHP files, returning an array of translations.
         * The filename should be the locale code, e.g. `en.php`.
         */
        public string $translationDir,

        /**
         * The default locale to use when none is provided.
         *
         * If not set, {@see Locale::getDefault()} will be used.
         * The default locale should be one of the available locales.
         */
        public ?string $defaultLocale = null,

        /**
         * The available locales for the application.
         *
         * @param array<string> $availableLocales
         */
        array $availableLocales = ['en'],

        /**
         * Collect missing translations during execution of the application.
         *
         * Note: This value is resolved at compile time only, so do not use environment variables to set it.
         */
        public bool $collectTranslations = false,

        /**
         * List of locales to collect missing translations.
         * If not set, will use the available locales.
         *
         * @var array<string>|null
         */
        public ?array $collectedLocales = null,

        /**
         * Define the output file for the collector translator.
         * If not set, will save into {@see TranslationConfig::$translationDir} as `{locale}.missing.php`.
         *
         * Note: This value is resolved at compile time only, so do not use environment variables to set it.
         */
        public ?string $collectorOutputFile = null,

        /**
         * Enable pseudo localization translator.
         * This should be used for testing purposes only.
         *
         * The pseudo localization will expend the text, and use special characters to simulate a different language.
         *
         * Pseudo localization cannot be used with the collector translator.
         *
         * Note: This value is resolved at compile time only, so do not use environment variables to set it.
         */
        public bool $pseudoLocalization = false,

        /**
         * The expansion factor for pseudo localization.
         */
        public float $pseudoLocalizationExpansionFactor = 1.5,

        /**
         * Replace characters with accents in pseudo localization.
         */
        public bool $pseudoLocalizationAccents = true,

        /**
         * Add brackets around pseudo localized text.
         */
        public bool $pseudoLocalizationBrackets = true,
    ) {
        /** @var array<string, string> */
        $this->availableLocales = array_combine($availableLocales, $availableLocales);
    }

    /**
     * @param array<string>|null $availableLocales
     * @param array<string>|null $collectedLocales
     */
    public function with(
        ?string $defaultLocale = null,
        ?string $translationDir = null,
        ?array $availableLocales = null,
        ?bool $collectTranslations = null,
        ?array $collectedLocales = null,
        ?string $collectorOutputFile = null,
        ?bool $pseudoLocalization = null,
        ?float $pseudoLocalizationExpansionFactor = null,
        ?bool $pseudoLocalizationAccents = null,
        ?bool $pseudoLocalizationBrackets = null,
    ): self {
        return new self(
            translationDir: $translationDir ?? $this->translationDir,
            defaultLocale: $defaultLocale ?? $this->defaultLocale,
            availableLocales: $availableLocales ?? $this->availableLocales,
            collectTranslations: $collectTranslations ?? $this->collectTranslations,
            collectedLocales: $collectedLocales ?? $this->collectedLocales,
            collectorOutputFile: $collectorOutputFile ?? $this->collectorOutputFile,
            pseudoLocalization: $pseudoLocalization ?? $this->pseudoLocalization,
            pseudoLocalizationExpansionFactor: $pseudoLocalizationExpansionFactor ?? $this->pseudoLocalizationExpansionFactor,
            pseudoLocalizationAccents: $pseudoLocalizationAccents ?? $this->pseudoLocalizationAccents,
            pseudoLocalizationBrackets: $pseudoLocalizationBrackets ?? $this->pseudoLocalizationBrackets,
        );
    }

    public static function default(Application $app): self
    {
        return new self(
            translationDir: $app->projectDir() . '/translations',
        );
    }
}
