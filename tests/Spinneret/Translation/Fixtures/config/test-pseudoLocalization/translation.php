<?php

use Arakne\Spinneret\Translation\TranslationConfig;

return fn (TranslationConfig $config)  => new TranslationConfig(
    defaultLocale: $config->defaultLocale,
    translationDir: $config->translationDir,
    availableLocales: $config->availableLocales,
    pseudoLocalization: true,
    pseudoLocalizationExpansionFactor: 1.0,
);
