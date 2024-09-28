<?php

namespace Arakne\Tests\Spinneret\Application\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\Compiler\ContainerCompiler;
use Arakne\Spinneret\Util\Files;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ContainerCompilerTest extends TestCase
{
    private Application $app;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = '/tmp/container_compiler_test';
        $this->clearCache();
        $this->app = new class($this->cacheDir) extends Application {
            public function __construct(private string $cacheDir)
            {
                parent::__construct();
            }

            public function cacheDir(): string
            {
                return $this->cacheDir;
            }
        };

        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Files::rmdir($this->cacheDir);
    }

    #[Test]
    public function loadAndCompile()
    {
        $compiler = new ContainerCompiler();

        $this->assertNull($compiler->load($this->app));

        $container = new ContainerBuilder();
        $container->setDefinition('service', new Definition('stdClass'))->setPublic(true);
        $container->compile();

        $compiler->compile($this->app, $container);

        $this->assertDirectoryExists($this->cacheDir);

        $compiledContainer = $compiler->load($this->app);
        $this->assertInstanceOf(Container::class, $compiledContainer);

        $this->assertInstanceOf(\stdClass::class, $compiledContainer->get('service'));
    }

    #[Test]
    public function customSavePath()
    {
        $compiler = new ContainerCompiler('foo');

        $this->assertNull($compiler->load($this->app));

        $container = new ContainerBuilder();
        $container->setDefinition('service', new Definition('stdClass'))->setPublic(true);
        $container->compile();

        $compiler->compile($this->app, $container);

        $this->assertDirectoryExists($this->cacheDir.'/foo');

        $compiledContainer = $compiler->load($this->app);
        $this->assertInstanceOf(Container::class, $compiledContainer);

        $this->assertInstanceOf(\stdClass::class, $compiledContainer->get('service'));
    }

    #[Test]
    public function loadInvalidFile()
    {
        $compiler = new ContainerCompiler();

        $container = new ContainerBuilder();
        $container->compile();
        $compiler->compile($this->app, $container);

        foreach (scandir($this->cacheDir) as $file) {
            $filepath = $this->cacheDir . '/' . $file;

            if (!\is_file($filepath)) {
                continue;
            }

            file_put_contents($filepath, '<?php return "invalid";');
        }

        $this->assertNull($compiler->load($this->app));

        foreach (scandir($this->cacheDir) as $file) {
            $filepath = $this->cacheDir . '/' . $file;

            if (!\is_file($filepath)) {
                continue;
            }

            file_put_contents($this->cacheDir . '/' . $file, '<?php syntax!error;');
        }
        $this->assertNull($compiler->load($this->app));
    }
}
