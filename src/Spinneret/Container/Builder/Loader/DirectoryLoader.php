<?php

namespace Arakne\Spinneret\Container\Builder\Loader;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

use function assert;
use function class_exists;
use function ltrim;
use function str_replace;
use function strlen;
use function substr;

/**
 * Register all classes in a directory.
 * Files will be iterated recursively.
 */
final readonly class DirectoryLoader
{
    public function __construct(
        /**
         * The directory to load classes from.
         */
        private string $directory,

        /**
         * The namespace to prepend to the class names.
         * Class must follow PSR-4 standards.
         *
         * It's not required to end with a backslash.
         */
        private string $namespace = '',
    ) {}

    /**
     * Load all classes in the specified directory into the container builder.
     *
     * @param ContainerBuilder $builder
     * @return void
     */
    public function load(ContainerBuilder $builder): void
    {
        $namespace = $this->namespace;
        $path = $this->directory;

        if ($namespace !== '' && $namespace[-1] !== '\\') {
            $namespace .= '\\';
        }

        $pathLen = strlen($path);

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($it as $file) {
            assert($file instanceof SplFileInfo);

            $classBaseName = $file->getBasename('.php');
            $classNamespace = $namespace . str_replace('/', '\\', substr($file->getPath(), $pathLen + 1));

            if ($classNamespace !== '' && $classNamespace[-1] !== '\\') {
                $classNamespace .= '\\';
            }

            $className = ltrim($classNamespace, '\\') . $classBaseName;

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if (
                $builder->defined($reflection->name)
                || $reflection->isInterface()
                || $reflection->isAbstract()
                || $reflection->isTrait()
                || $reflection->isEnum()
            ) {
                continue;
            }

            $builder->register($reflection->name)->ignoreIfInvalid = true;
        }
    }
}
