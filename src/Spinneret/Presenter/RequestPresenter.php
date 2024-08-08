<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Router\RoutedRequest;
use Override;

/**
 * Simple presenter which returns the request as is.
 * So it can be used to directly render the request as a response.
 */
final class RequestPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return $request;
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        return $request;
    }
}
