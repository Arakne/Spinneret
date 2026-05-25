<?php

namespace Arakne\Spinneret\Runner;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Interface for exceptions that can be converted to a request object that will be rerouted to the presenter.
 */
interface RequestProviderExceptionInterface extends Throwable
{
    /**
     * Convert the exception to a request object that can be handled by the presenter.
     *
     * @param object $baseRequest The base request object that would have been resolved by the router if the error did not occur.
     * @param ServerRequestInterface $psrRequest The original PSR-7 request that caused the error. It can be used to extract additional information for the presenter.
     */
    public function toRequest(object $baseRequest, ServerRequestInterface $psrRequest): object;
}
