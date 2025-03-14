<?php

namespace Arakne\Spinneret\Runner;

use Arakne\Spinneret\Router\RoutedRequest;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The runner is responsible for handling the HTTP request and returning a response.
 *
 * Steps are:
 * - Call middlewares to apply transformations to the request
 * - Resolve the request class, and instantiate it using router
 * - Dispatch the created request to the presenter
 * - Render the response using the view engine
 */
interface RunnerInterface extends RequestHandlerInterface
{
    /**
     * Handle the HTTP request and return a response.
     *
     * This method should not throw exceptions,
     * instead a {@see InternalServerError} dummy request should be created and handled.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Handle and already resolved request and return a response.
     *
     * Unlike {@see RunnerInterface::handle()}, router and middlewares are not called.
     *
     * @param RoutedRequest $routedRequest
     * @param bool $catch If true, {@see InternalServerError} will be dispatched on error. Otherwise, the error will be thrown.
     *
     * @return ResponseInterface
     */
    public function handleRoutedRequest(RoutedRequest $routedRequest, bool $catch = true): ResponseInterface;
}
