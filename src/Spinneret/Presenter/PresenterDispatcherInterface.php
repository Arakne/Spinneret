<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Router\RoutedRequest;
use LogicException;

/**
 * Dispatches the appropriate presenter for a request.
 */
interface PresenterDispatcherInterface
{
    /**
     * Handle the request by dispatching the appropriate presenter.
     *
     * The class name of {@see RoutedRequest::$routedRequest} is used to determine the presenter to use.
     *
     * When {@see RoutedRequest::$success} is true, the presenter's {@see PresenterInterface::handleSuccess()} method is called.
     * Otherwise, the presenter's {@see PresenterInterface::handleError()} method is called.
     *
     * @param RoutedRequest $routedRequest The request to handle. Created by the router.
     *
     * @return object The response DTO.
     *
     * @throws LogicException If no presenter is found for the request.
     */
    public function dispatch(RoutedRequest $routedRequest): object;
}
