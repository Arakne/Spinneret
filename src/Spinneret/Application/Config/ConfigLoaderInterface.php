<?php

namespace Arakne\Spinneret\Application\Config;

use Arakne\Spinneret\Application\Application;

/**
 * Base type for loading configuration objects of the application
 */
interface ConfigLoaderInterface
{
    /**
     * Load configuration objects of the application
     *
     * The returned array is indexed by the class name of the configuration object.
     *
     * @return array<class-string, object>
     * @psalm-return class-string-map<T, T>
     */
    public function load(Application $app): array;
}
