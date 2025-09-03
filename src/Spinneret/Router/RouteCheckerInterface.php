<?php

namespace Arakne\Spinneret\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface used by the router to check if a resolved route is valid for the given request.
 * This can be used to implement custom logic, like checking for authentication, etc.
 */
interface RouteCheckerInterface
{
    /**
     * Check the resolved route for the given request.
     *
     * The check must return null if the route is valid, so the router can continue its process,
     * and perform the DTO instantiation and validation.
     *
     * When the checker returns an object, the router will stop its process and return this object instead.
     * So, this object can be used at the presenter layer to process the error.
     *
     * @param ServerRequestInterface $request The current request
     * @param class-string $target The resolved target DTO class name
     * @param array<string, mixed> $attributes The resolved route attributes
     *
     * @return object|null The fallback object to return if the route is not valid, or null if the route is valid
     */
    public function check(ServerRequestInterface $request, string $target, array $attributes): ?object;
}
