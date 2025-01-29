<?php

namespace Arakne\Spinneret\Router\Result;

/**
 * The route or the resource was not found
 *
 * This object may be returned by the router when the route is not found,
 * or manually by a presenter when the resource is not found.
 *
 * When this object occurs, as request from router or as response from presenter,
 * it should result in a 404 HTTP status code.
 */
final readonly class NotFound
{
    public function __construct(
        /**
         * The error message to display
         */
        public ?string $message = null,
    ) {}
}
