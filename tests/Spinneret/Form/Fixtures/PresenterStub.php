<?php

namespace Arakne\Tests\Spinneret\Form\Fixtures;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Closure;
use Override;

class PresenterStub implements PresenterInterface
{
    public static Closure $handleSuccess;
    public static Closure $handleError;

    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return (self::$handleSuccess)($request, $routedRequest);
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        return (self::$handleError)($request, $routedRequest);
    }
}
