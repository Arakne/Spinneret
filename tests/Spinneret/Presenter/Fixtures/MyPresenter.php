<?php

namespace Arakne\Tests\Spinneret\Presenter\Fixtures;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Override;
use stdClass;

class MyPresenter implements PresenterInterface
{
    public object $request;
    public RoutedRequest $routedRequest;
    public string $method;

    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        $this->request = $request;
        $this->routedRequest = $routedRequest;
        $this->method = 'handleSuccess';

        return (object) ['ok' => true];
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        $this->request = $request;
        $this->routedRequest = $routedRequest;
        $this->method = 'handleError';

        return (object) ['ok' => false];
    }
}
