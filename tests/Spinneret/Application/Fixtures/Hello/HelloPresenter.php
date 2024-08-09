<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Hello;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Override;

/**
 * @implements PresenterInterface<HelloRequest>
 */
final class HelloPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): HelloResponse
    {
        return new HelloResponse($request->name ?? 'World');
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        var_dump($request, $routedRequest->form->errors());
    }
}
