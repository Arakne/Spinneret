<?php

namespace Arakne\Tests\Spinneret\Fixtures\Error;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Override;
use RuntimeException;

/**
 * @implements PresenterInterface<RaiseErrorRequest>
 */
class RaiseErrorPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        throw new RuntimeException('Error');
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        // TODO: Implement handleError() method.
    }
}
