<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Translation\TranslationConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TranslationConfigTest extends TestCase
{
    #[Test]
    public function with()
    {

        $config = TranslationConfig::default(new Application())->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
        );

        $new = $config->with(defaultLocale: 'fr');

        $this->assertNotEquals($config, $new);
        $this->assertSame('fr', $new->defaultLocale);
        $this->assertSame(['en' => 'en', 'fr' => 'fr', 'es' => 'es'], $new->availableLocales);
    }
}
