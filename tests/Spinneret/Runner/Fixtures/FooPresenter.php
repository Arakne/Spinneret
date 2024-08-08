<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Override;

class FooPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        if ($request->bar === 'error') {
            throw new \Exception('runtime error');
        }

        return new FooSuccessResponse('success ' . $request->bar);
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        return new FooErrorResponse('error ' . $routedRequest->form->errors()['bar']->message);
    }
}
