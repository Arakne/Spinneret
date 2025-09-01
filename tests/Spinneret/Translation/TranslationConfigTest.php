<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Translation\TranslationConfig;
use Arakne\Spinneret\Util\Files;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function clearstatcache;

class TranslationConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        Files::rmdir(__DIR__.'/../../../var/cache/test-translation');
        clearstatcache();
    }

    #[Test]
    public function with()
    {
        $config = TranslationConfig::default(new Application(env: 'test-translation'))->with(
            defaultLocale: 'en',
            availableLocales: ['en', 'fr', 'es'],
        );

        $new = $config->with(defaultLocale: 'fr');

        $this->assertNotEquals($config, $new);
        $this->assertSame('fr', $new->defaultLocale);
        $this->assertSame(['en' => 'en', 'fr' => 'fr', 'es' => 'es'], $new->availableLocales);
    }
}
