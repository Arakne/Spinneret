<?php

namespace Arakne\Spinneret\Runner;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Request object representing an exception during the runner execution
 * It should result in a 500 Internal Server Error response, and log the error
 */
final readonly class InternalServerError
{
    public function __construct(
        /**
         * The step at which the error occurred
         */
        public RunnerStepEnum $step,

        /**
         * The actual error
         */
        public Throwable $error,

        /**
         * The handled PSR-7 request
         */
        public ServerRequestInterface $psrRequest,

        /**
         * The request object resolved by the router
         * Can be null if the error occurred before the router
         *
         * This value is set when:
         * - {@see RunnerInterface::handleRoutedRequest()} is called directly (i.e. sub-request)
         * - The error occurs on the presenter or view rendering
         */
        public ?object $request = null,

        /**
         * The response object resolved by the presenter
         * Can be null if the error occurred before the presenter
         *
         * This value is set only when the error occurs on the view rendering
         */
        public ?object $response = null,
    ) {}
}
