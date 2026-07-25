<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Result\NotFound;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base interface for resolve requests from PSR-7 requests
 * The implementation must be stateless.
 */
interface RouterInterface
{
    /**
     * Resolve the request and instantiate it from the PSR-7 request
     *
     * Attributes declared on routes will be added to the request as attributes.
     *
     * When the route cannot be found (or doesn't match constraints), {@see NotFound} request will be resolved.
     * When the route is found but doesn't match with allowed HTTP methods, {@see MethodNotAllowed} request will be resolved.
     *
     * Not exception should be thrown by this method, unless the routes configuration is invalid.
     *
     * @param ServerRequestInterface $request The incoming request
     * @return RoutedRequest The resolved request
     */
    public function request(ServerRequestInterface $request): RoutedRequest;
}
