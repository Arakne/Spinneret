<?php

namespace Arakne\Spinneret\Application\Compiler;

use Arakne\Spinneret\Application\Application;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Throwable;

use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function preg_replace;

/**
 * Default implementation of {@see ContainerCompilerInterface}.
 * Use {@see PhpDumper} to compile the container into multiple PHP files.
 */
final readonly class ContainerCompiler implements ContainerCompilerInterface
{
    public function __construct(
        /**
         * The save directory path, relative to the cache directory
         */
        private string $savePath = '',
    ) {
    }

    #[Override]
    public function load(Application $application): ?ContainerInterface
    {
        $containerClassPath = $application->cacheDir().'/'.$this->savePath.'/'.$this->containerClassName($application).'.php';

        if (!is_file($containerClassPath)) {
            return null;
        }

        try {
            $container = include $containerClassPath;
        } catch (Throwable) {
            return null;
        }

        if (!$container instanceof ContainerInterface) {
            return null;
        }

        return $container;
    }

    #[Override]
    public function compile(Application $application, ContainerBuilder $container): void
    {
        $dumper = new PhpDumper($container);

        /** @var array<string, string> $files */
        $files = $dumper->dump([
            'as_files' => true,
            'class' => $this->containerClassName($application),
        ]);

        $basePath = $application->cacheDir().'/'.$this->savePath;

        foreach ($files as $file => $content) {
            $fullPath = $basePath.'/'.$file;
            $dir = dirname($fullPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0o777, true);
            }

            file_put_contents($fullPath, $content);
        }
    }

    private function containerClassName(Application $application): string
    {
        return preg_replace('/[^A-Z0-9_]/i', '_', $application::class).'Container';
    }
}
