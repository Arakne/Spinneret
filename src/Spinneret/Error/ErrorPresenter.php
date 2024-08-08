<?php

namespace Arakne\Spinneret\Error;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Runner\InternalServerError;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Presenter for handle internal server error.
 *
 * The presenter will write the error to the logger, and directly return the InternalServerError object.
 *
 * @implements PresenterInterface<InternalServerError>
 */
final readonly class ErrorPresenter implements PresenterInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return $this->handleError($request, $routedRequest);
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        $this->logger?->error(
            'Uncaught exception : ' . $request->error,
            (array) $request,
        );

        return $request;
    }
}
