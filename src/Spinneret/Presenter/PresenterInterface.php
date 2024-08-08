<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Router\RoutedRequest;

/**
 * @template R as object
 */
interface PresenterInterface
{
    /**
     * @param R $request
     * @param RoutedRequest $routedRequest
     * @return object
     */
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object;

    /**
     * @param R $request
     * @param RoutedRequest $routedRequest
     * @return object
     */
    public function handleError(object $request, RoutedRequest $routedRequest): object;
}
