<?php

namespace Arakne\Spinneret\Util;

use ReflectionClass;
use RuntimeException;

use function dirname;
use function is_file;

/**
 * Helper methods related to the project.
 */
final class Project
{
    /**
     * Try to locate the project root directory.
     * This method will look for the first directory containing a composer.json file.
     *
     * @param class-string $applicationClass
     * @return string
     *
     * @throws RuntimeException if the project root directory could not be located (no composer.json file found)
     * @throws \ReflectionException if the application class could not be reflected
     */
    public static function directory(string $applicationClass): string
    {
        $r = new ReflectionClass($applicationClass);
        $currentDir = dirname($r->getFileName());

        while (!is_file($currentDir.'/composer.json')) {
            $parentDir = dirname($currentDir);

            if ($parentDir === $currentDir) {
                throw new RuntimeException('Could not locate the project root directory.');
            }

            $currentDir = $parentDir;
        }

        return $currentDir;
    }
}
