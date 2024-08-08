<?php

namespace Arakne\Tests\Spinneret\Application\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\Compiler\ContainerCompiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ContainerCompilerTest extends TestCase
{
    const CACHE_DIR = '/tmp/container_compiler_test';

    protected function setUp(): void
    {
        $this->clearCache();
        $this->app = new class extends Application {
            public function cacheDir(): string
            {
                return ContainerCompilerTest::CACHE_DIR;
            }
        };

        $this->clearCache();
    }

    private function clearCache(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::CACHE_DIR, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
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

        $this->assertDirectoryExists(self::CACHE_DIR);

        $compiledContainer = $compiler->load($this->app);
        $this->assertInstanceOf(Container::class, $compiledContainer);

        $this->assertInstanceOf(\stdClass::class, $compiledContainer->get('service'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function loadInvalidFile()
    {
        $compiler = new ContainerCompiler();

        $container = new ContainerBuilder();
        $container->compile();
        $compiler->compile($this->app, $container);

        foreach (scandir(self::CACHE_DIR) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            file_put_contents(self::CACHE_DIR . '/' . $file, '<?php return "invalid";');
        }

        $this->assertNull($compiler->load($this->app));

        foreach (scandir(self::CACHE_DIR) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            file_put_contents(self::CACHE_DIR . '/' . $file, '<?php syntax!error;');
        }
        $this->assertNull($compiler->load($this->app));
    }
}
