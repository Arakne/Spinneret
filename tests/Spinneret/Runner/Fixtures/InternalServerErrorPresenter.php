<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;

class InternalServerErrorPresenter implements PresenterInterface
{
    public static RoutedRequest $lastRequest;

    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        self::$lastRequest = $routedRequest;
        return $request;
    }

    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        self::$lastRequest = $routedRequest;
        return $request;
    }
}