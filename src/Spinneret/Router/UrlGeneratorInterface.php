<?php

namespace Arakne\Spinneret\Router;

/**
 * Interface for generate URLs from request objects
 */
interface UrlGeneratorInterface
{
    /**
     * Generate an URL for a request class with the given parameters
     *
     * @param class-string $requestClass The request class to generate the URL for. Must be registered in the router.
     * @param array<string, mixed> $parameters The parameters to pass to the URL generator
     *
     * @return string The generated URL. The URL will be absolute.
     */
    public function url(string $requestClass, array $parameters = []): string;
}
