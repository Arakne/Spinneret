<?php

use Arakne\Spinneret\Translation\TranslationConfig;

return fn (TranslationConfig $config) => new TranslationConfig(
    defaultLocale: $config->defaultLocale,
    translationDir: $config->translationDir,
    availableLocales: $config->availableLocales,
    collectTranslations: true,
    collectedLocales: ['fr', 'es'],
    collectorOutputFile: '/tmp/'.bin2hex(random_bytes(8)).'/{locale}.php',
);
