<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Forward;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;

class TestForwardPresenter implements PresenterInterface
{
    #[\Override] public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return new TestForwardResponse();
    }

    #[\Override] public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        // TODO: Implement handleError() method.
    }
}