<?php

namespace Arakne\Spinneret\Translation;

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

    public function __construct(
        /**
         * The default locale to use when none is provided.
         *
         * If not set, {@see Locale::getDefault()} will be used.
         * The default locale should be one of the available locales.
         */
        public ?string $defaultLocale = null,

        /**
         * The directory where translation files are stored.
         *
         * The translations must be stored as PHP files, returning an array of translations.
         * The filename should be the locale code, e.g. `en.php`.
         */
        public string $translationDir = '%app.project_dir%/translations',

        /**
         * The available locales for the application.
         *
         * @param list<string> $availableLocales
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
         * @var list<string>|null
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
        $this->availableLocales = array_combine($availableLocales, $availableLocales);
    }
}
