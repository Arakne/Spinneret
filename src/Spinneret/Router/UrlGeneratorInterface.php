<?php

namespace Arakne\Spinneret\Router;

/**
 * Interface for generate URLs from request objects
 */
interface UrlGeneratorInterface
{
    public function url(string $requestClass, array $parameters = []): string;
}
