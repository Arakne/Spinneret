<?php

namespace Arakne\Tests\Spinneret\Application\Config;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\Config\PhpConfigLoader;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\FooConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonsConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhpConfigLoaderTest extends TestCase
{
    const CACHE_DIR = '/tmp/php-config-loader-test';

    protected function tearDown(): void
    {
        Files::rmdir(self::CACHE_DIR);
    }

    #[Test]
    public function loadSimple()
    {
        $app = $this->createApp(__DIR__ . '/Fixtures/simple');
        $loader = new PhpConfigLoader();
        $config = $loader->load($app);

        $this->assertEquals([
            FooConfig::class => new FooConfig(foo: 'baz'),
            PersonsConfig::class => new PersonsConfig(
                new PersonConfig(
                    firstName: 'John',
                    lastName: 'Doe',
                    age: 42,
                ),
                new PersonConfig(
                    firstName: 'Robert',
                    lastName: 'Smith',
                ),
            )
        ], $config);

        $this->assertFileExists(self::CACHE_DIR . '/config.php');
        $this->assertEquals(<<<'PHP'
<?php

return static function (Arakne\Spinneret\Application\Application $app): array {
    $configPath = $app->configDir();

    return [
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\FooConfig' => require $configPath . '/foo.php',
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\PersonsConfig' => require $configPath . '/persons.php',

    ];
};
PHP
, file_get_contents(self::CACHE_DIR . '/config.php')
);

        $cachedConfig = $loader->load($app);
        $this->assertEquals($config, $cachedConfig);
    }

    #[Test]
    public function loadClosureWithoutParameter()
    {
        $app = $this->createApp(__DIR__ . '/Fixtures/simple-closure');
        $loader = new PhpConfigLoader();
        $config = $loader->load($app);

        $this->assertEquals([
            FooConfig::class => new FooConfig(foo: 'baz'),
            PersonsConfig::class => new PersonsConfig(
                new PersonConfig(
                    firstName: 'John',
                    lastName: 'Doe',
                    age: 42,
                ),
                new PersonConfig(
                    firstName: 'Robert',
                    lastName: 'Smith',
                ),
            )
        ], $config);

        $this->assertFileExists(self::CACHE_DIR . '/config.php');
        $this->assertEquals(<<<'PHP'
<?php

return static function (Arakne\Spinneret\Application\Application $app): array {
    $configPath = $app->configDir();

    return [
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\FooConfig' => (require $configPath . '/foo.php')(),
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\PersonsConfig' => (require $configPath . '/persons.php')(),

    ];
};
PHP
, file_get_contents(self::CACHE_DIR . '/config.php')
);

        $cachedConfig = $loader->load($app);
        $this->assertEquals($config, $cachedConfig);
    }

    #[Test]
    public function loadWithExtensionClosure()
    {
        $app = $this->createApp(__DIR__ . '/Fixtures/extension-closure');
        $loader = new PhpConfigLoader();
        $config = $loader->load($app);

        $this->assertEquals([
            FooConfig::class => new FooConfig(foo: 'BAZ'),
            PersonsConfig::class => new PersonsConfig(
                new PersonConfig(
                    firstName: 'John',
                    lastName: 'Doe',
                    age: 42,
                ),
                new PersonConfig(
                    firstName: 'Robert',
                    lastName: 'Smith',
                ),
            )
        ], $config);

        $this->assertFileExists(self::CACHE_DIR . '/config.php');
        $this->assertEquals(<<<'PHP'
<?php

return static function (Arakne\Spinneret\Application\Application $app): array {
    $configPath = $app->configDir();

    return [
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\FooConfig' => (require $configPath . '/foo2.php')((require $configPath . '/foo.php')()),
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\PersonsConfig' => (require $configPath . '/robert.php')((require $configPath . '/persons.php')()),

    ];
};
PHP
, file_get_contents(self::CACHE_DIR . '/config.php')
);

        $cachedConfig = $loader->load($app);
        $this->assertEquals($config, $cachedConfig);
    }

    #[Test]
    public function withInvalidArgumentCount()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#Invalid config file .*/invalid-arg-count/invalid-arg-count.php : the closure can take at most the application and the previous config object.#');

        $app = $this->createApp(__DIR__ . '/Fixtures/invalid-arg-count');
        $loader = new PhpConfigLoader();
        $loader->load($app);
    }

    #[Test]
    public function withMissingTypehint()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#Invalid type for parameter foo of config file .*/missing-typehint/invalid.php : it must be an atomic nullable class.#');

        $app = $this->createApp(__DIR__ . '/Fixtures/missing-typehint');
        $loader = new PhpConfigLoader();
        $loader->load($app);
    }

    #[Test]
    public function withNotAtomicTypehint()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('#Invalid type for parameter foo of config file .*/not-atomic-typehint/invalid.php : it must be an atomic nullable class.#');

        $app = $this->createApp(__DIR__ . '/Fixtures/not-atomic-typehint');
        $loader = new PhpConfigLoader();
        $loader->load($app);
    }

    #[Test]
    public function loadWithEnv()
    {
        $app = $this->createApp(__DIR__ . '/Fixtures/with-env');
        $loader = new PhpConfigLoader();
        $config = $loader->load($app);

        $this->assertEquals([
            FooConfig::class => new FooConfig(foo: 'BAZ'),
            PersonsConfig::class => new PersonsConfig(
                new PersonConfig(
                    firstName: 'John',
                    lastName: 'Doe',
                    age: 42,
                ),
                new PersonConfig(
                    firstName: 'Robert',
                    lastName: 'Smith',
                ),
                new PersonConfig(
                    firstName: 'Anne',
                    lastName: 'Parker',
                ),
            )
        ], $config);

        $this->assertFileExists(self::CACHE_DIR . '/config.php');
        $this->assertEquals(<<<'PHP'
<?php

return static function (Arakne\Spinneret\Application\Application $app): array {
    $configPath = $app->configDir();

    return [
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\FooConfig' => (require $configPath . '/test/foo.php')(require $configPath . '/foo.php'),
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\PersonsConfig' => (require $configPath . '/test/persons.php')(require $configPath . '/persons.php'),

    ];
};
PHP
            , file_get_contents(self::CACHE_DIR . '/config.php')
        );

        $cachedConfig = $loader->load($app);
        $this->assertEquals($config, $cachedConfig);
    }

    #[Test]
    public function loadWithApp()
    {
        $app = $this->createApp(__DIR__ . '/Fixtures/with-app');
        $loader = new PhpConfigLoader();
        $config = $loader->load($app);

        $this->assertEquals([
            FooConfig::class => new FooConfig(foo: 'test-foo'),
            PersonsConfig::class => new PersonsConfig(
                new PersonConfig(
                    firstName: 'Robert',
                    lastName: 'Smith',
                ),
                new PersonConfig(
                    firstName: 'test',
                    lastName: 'test',
                ),
            )
        ], $config);

        $this->assertFileExists(self::CACHE_DIR . '/config.php');
        $this->assertEquals(<<<'PHP'
<?php

return static function (Arakne\Spinneret\Application\Application $app): array {
    $configPath = $app->configDir();

    return [
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\FooConfig' => (require $configPath . '/foo.php')($app),
		'Arakne\\Tests\\Spinneret\\Application\\Config\\Fixtures\\PersonsConfig' => (require $configPath . '/test/persons.php')(require $configPath . '/persons.php', $app),

    ];
};
PHP
            , file_get_contents(self::CACHE_DIR . '/config.php')
        );

        $cachedConfig = $loader->load($app);
        $this->assertEquals($config, $cachedConfig);
    }

    private function createApp(string $configDir): Application
    {
        return new class($configDir) extends Application {
            public function __construct(
                private readonly string $configDir,
            ) {
                parent::__construct(env: 'test');
            }

            public function cacheDir(): string
            {
                return PhpConfigLoaderTest::CACHE_DIR;
            }

            public function configDir(): string
            {
                return $this->configDir;
            }
        };
    }
}
