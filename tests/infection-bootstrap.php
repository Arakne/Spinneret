<?php

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/bootstrap.php';

// Infection needs to autoload all generated classes because it doesn't run includes
// So register here cache as a PSR-4 namespace + load GeneratedFieldsExtractor from compiled routes
$loader->addPsr4('', __DIR__.'/../var/cache');
spl_autoload_register(function (string $className){
    if (str_starts_with($className, 'GeneratedFieldsExtractor')) {
        @include __DIR__.'/../var/cache/compiled_routes.php';
    }
});

function _infection_rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    try {
        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach (new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    } catch (Throwable) {
        // Ignore
    }
}

// Infection will generates invalid generated container, so we need to clean it
_infection_rrmdir(__DIR__.'/../var/cache');

// Infection will also generate some directories directly on project root, so we need to clean them at the end
register_shutdown_function(function () {
    $filesToDelete = [
        ...glob(__DIR__.'/../Arakne_Spinneret_Application_*.php'),
        ...glob(__DIR__.'/../*Container.php'),
        ...glob(__DIR__.'/../*Container.preload.php'),
        __DIR__.'/../compiled_routes.php',
    ];

    foreach ($filesToDelete as $file) {
        @unlink($file);
    }

    $directoriesToDelete = [
        ...glob(__DIR__.'/../Container*', GLOB_ONLYDIR),
        __DIR__.'/../src/Spinneret/Application/var',
        __DIR__.'/../var/cache',
    ];

    foreach ($directoriesToDelete as $dir) {
        _infection_rrmdir($dir);
    }
});
