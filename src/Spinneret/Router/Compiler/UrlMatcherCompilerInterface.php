<?php

namespace Arakne\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Base type for handle url matcher compilation
 *
 * Allows to load a compiled url matcher if it exists, or compile it
 */
interface UrlMatcherCompilerInterface
{
    /**
     * Try to load an already compiled url matcher
     *
     * If the compiled url matcher does not exist, or is invalid, returns null.
     * This method should not throw exceptions.
     *
     * @param Application $application The current application. Used to get the cache directory.
     * @param RequestContext $context The request context to use in the url matcher.
     *
     * @return UrlMatcherInterface|null The compiled url matcher, or null if it cannot be loaded.
     */
    public function load(Application $application, RequestContext $context): ?UrlMatcherInterface;

    /**
     * Compile the url matcher and save it
     *
     * @param Application $application The current application. Used to get the cache directory.
     * @param RouteCollection $routes The route collection to compile. This parameter must not be modified by the compiler.
     *
     * @return void
     */
    public function compile(Application $application, RouteCollection $routes): void;
}
