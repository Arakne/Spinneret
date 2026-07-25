<?php

namespace Arakne\Spinneret\Router\Result;

/**
 * The route has been found but the HTTP method is not allowed
 *
 * This object may be returned by the router when the current HTTP methods doesn't match
 * with configured ones.
 *
 * When this object occurs it should result in a 405 HTTP status code, with Allow header.
 */
final readonly class MethodNotAllowed
{
    public function __construct(
        /**
         * The current HTTP method extracted from the request
         */
        public string $currentMethod,

        /**
         * List of allowed methods of the current route,
         * configured on the route definition
         *
         * @var list<string>
         */
        public array $allowedMethods,
    ) {}
}
