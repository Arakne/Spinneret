<?php

return new \Arakne\Spinneret\Translation\TranslationConfig(
    defaultLocale: 'en',
    translationDir: __DIR__ . '/../translations',
    availableLocales: ['en', 'fr', 'es'],
);
