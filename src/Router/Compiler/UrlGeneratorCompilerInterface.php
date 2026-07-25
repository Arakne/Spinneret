<?php

namespace Arakne\Spinneret\Router\Compiler;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Base type for handle url generator compilation
 *
 * Allows to load a compiled url generator if it exists, or compile it
 */
interface UrlGeneratorCompilerInterface
{
    /**
     * Try to load an already compiled url generator
     *
     * If the compiled url generator does not exist, or is invalid, returns null.
     * This method should not throw exceptions.
     *
     * @param Application $application The current application. Used to get the cache directory.
     * @param RequestContext $context The request context to use in the url generator.
     *
     * @return UrlGeneratorInterface|null The compiled url generator, or null if it cannot be loaded.
     */
    public function load(Application $application, RequestContext $context): ?UrlGeneratorInterface;

    /**
     * Compile the url generator and save it
     *
     * @param Application $application The current application. Used to get the cache directory.
     * @param RouteCollection $routes The route collection to compile. This parameter must not be modified by the compiler.
     *
     * @return void
     */
    public function compile(Application $application, RouteCollection $routes): void;
}
