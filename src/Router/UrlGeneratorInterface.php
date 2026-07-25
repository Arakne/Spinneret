<?php

namespace Arakne\Spinneret\Router;

/**
 * Interface for generate URLs from request objects
 */
interface UrlGeneratorInterface
{
    /**
     * Generate an URL for a request with the given parameters
     *
     * @param class-string|object $request The request class to generate the URL for. Must be registered in the router.
     *                                     If an object is given, its class will be used, and its properties will be used as parameters.
     *                                     Note: values will not be transformed by the form system, so make sure to provide values in the correct format.
     * @param array<string, mixed> $parameters The parameters to pass to the URL generator. If a parameter is already set in the request object (when an object is given),
     *                                         this parameter will take precedence over the object's property.
     *
     * @return string The generated URL. The URL will be absolute.
     */
    public function url(string|object $request, array $parameters = []): string;
}
