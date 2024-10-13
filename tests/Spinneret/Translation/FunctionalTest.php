<?php

namespace Arakne\Tests\Spinneret\Translation;

use Arakne\Spinneret\Translation\CollectorTranslator;
use Arakne\Spinneret\Translation\TranslationConfig;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Translation\Fixtures\Messages;
use Arakne\Tests\Spinneret\Translation\Fixtures\TranslationApplication;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function gc_collect_cycles;
use function var_dump;

class FunctionalTest extends TestCase
{
    #[Test]
    public function simple()
    {
        $app = new TranslationApplication(isDev: true, env: 'test');

        $this->assertSame([
            'Hello, World!',
            'Hello, John!',
            'Missing translation',
        ], $app->get(Messages::class)->show());

        $this->assertSame([
            'Bonjour tout le monde !',
            'Bonjour John !',
            'Missing translation',
        ], $app->get(Messages::class)->show('fr'));

        $this->assertSame([
            'Hello, World!',
            'Hello, John!',
            'Missing translation',
        ], $app->get(Messages::class)->show('es'));
    }

    #[Test]
    public function pseudoLocalization()
    {
        $app = new TranslationApplication(isDev: true, env: 'test-pseudoLocalization');

        $this->assertSame([
            '[Ĥéļļö، Ŵöŕļð¡]',
            '[Ĥéļļö، Ĵöĥñ¡]',
            '[Ṁîššîñĝ ţŕåñšļåţîöñ]',
        ], $app->get(Messages::class)->show());

        $this->assertSame([
            '[Ɓöñĵöûŕ ţöûţ ļé ɱöñðé ¡]',
            '[Ɓöñĵöûŕ Ĵöĥñ ¡]',
            '[Ṁîššîñĝ ţŕåñšļåţîöñ]',
        ], $app->get(Messages::class)->show('fr'));

        $this->assertSame([
            '[Ĥéļļö، Ŵöŕļð¡]',
            '[Ĥéļļö، Ĵöĥñ¡]',
            '[Ṁîššîñĝ ţŕåñšļåţîöñ]',
        ], $app->get(Messages::class)->show('es'));
    }

    #[Test]
    public function collector()
    {
        $app = new TranslationApplication(isDev: true, env: 'test-collect');

        $this->assertSame([
            'Hello, World!',
            'Hello, John!',
            'Missing translation',
        ], $app->get(Messages::class)->show());

        $dir = dirname($app->get(TranslationConfig::class)->collectorOutputFile);

        $translator = $app->get(Messages::class)->translator;
        $translator->dump();

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir.'/fr.php');
        $this->assertFileExists($dir.'/es.php');

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Missing translation' => 'Missing translation',
            );
            PHP,
            file_get_contents($dir.'/fr.php')
        );

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Hello, World!' => 'Hello, World!',
              'Hello, {name}!' => 'Hello, {name}!',
              'Missing translation' => 'Missing translation',
            );
            PHP,
            file_get_contents($dir.'/es.php')
        );

        Files::rmdir($dir);
    }

    #[Test]
    public function collectorShouldMergeIfFileExists()
    {
        $app = new TranslationApplication(isDev: true, env: 'test-collect');
        $dir = dirname($app->get(TranslationConfig::class)->collectorOutputFile);

        Files::write($dir.'/fr.php', <<<PHP
            <?php return array (
              'foo' => 'bar',
            );
            PHP
        );

        Files::write($dir.'/es.php', <<<PHP
            <?php return array (
              'foo' => 'bar',
            );
            PHP
        );

        $this->assertSame([
            'Hello, World!',
            'Hello, John!',
            'Missing translation',
        ], $app->get(Messages::class)->show());

        $translator = $app->get(Messages::class)->translator;
        $translator->dump();

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir.'/fr.php');
        $this->assertFileExists($dir.'/es.php');

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Missing translation' => 'Missing translation',
              'foo' => 'bar',
            );
            PHP,
            file_get_contents($dir.'/fr.php')
        );

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Hello, World!' => 'Hello, World!',
              'Hello, {name}!' => 'Hello, {name}!',
              'Missing translation' => 'Missing translation',
              'foo' => 'bar',
            );
            PHP,
            file_get_contents($dir.'/es.php')
        );

        Files::rmdir($dir);
    }

    #[Test]
    public function collectorShouldIgnoreInvalidFiles()
    {
        $app = new TranslationApplication(isDev: true, env: 'test-collect');
        $dir = dirname($app->get(TranslationConfig::class)->collectorOutputFile);

        Files::write($dir.'/fr.php', <<<PHP
            <?php sdfsdffsdfdff/->ddds,
            PHP
        );

        Files::write($dir.'/es.php', <<<PHP
            <?php return 123;
            PHP
        );

        $this->assertSame([
            'Hello, World!',
            'Hello, John!',
            'Missing translation',
        ], $app->get(Messages::class)->show());

        $translator = $app->get(Messages::class)->translator;
        $translator->dump();

        $this->assertDirectoryExists($dir);
        $this->assertFileExists($dir.'/fr.php');
        $this->assertFileExists($dir.'/es.php');

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Missing translation' => 'Missing translation',
            );
            PHP,
            file_get_contents($dir.'/fr.php')
        );

        $this->assertSame(<<<'PHP'
            <?php return array (
              'Hello, World!' => 'Hello, World!',
              'Hello, {name}!' => 'Hello, {name}!',
              'Missing translation' => 'Missing translation',
            );
            PHP,
            file_get_contents($dir.'/es.php')
        );

        Files::rmdir($dir);
    }
}
