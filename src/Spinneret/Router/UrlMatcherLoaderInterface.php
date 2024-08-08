<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Application\Application;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Used by {@see RouterModule} to load the symfony URL matcher
 */
interface UrlMatcherLoaderInterface
{
    /**
     * Loads the URL matcher
     * The loader may compile and load compiled matcher if needed
     *
     * @param Application $application The current application
     *
     * @return UrlMatcherInterface The loaded URL matcher
     */
    public function load(Application $application): UrlMatcherInterface;
}
