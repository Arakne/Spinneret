<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\SubmittedFormInterface;

/**
 * Result DTO of {@see RouterInterface::request()} method
 * This object is immutable
 */
final readonly class RoutedRequest
{
    public function __construct(
        /**
         * The incoming PSR-7 request
         *
         * This object may differ from the request passed as argument to the router,
         * as it will be filled with the attributes of the matched route (if any).
         */
        public ServerRequestInterface $psrRequest,

        /**
         * The request object resolved by the router and filled using PSR request values
         * This request may be invalid (e.g. missing or invalid data), depending on the {@see RoutedRequest::$success} value
         *
         * @var object
         */
        public object $routedRequest,

        /**
         * The route has been successfully matched, and the request object has been filled and validated by the form
         *
         * A false value may indicate:
         * - The route has not been found or has invalid HTTP method
         * - The validation of the request object using form has failed (e.g. missing or invalid data)
         * - An internal request for error value
         *
         * If this value is true, {@see PresenterInterface::handleSuccess()} should be called
         * If this value is false, {@see PresenterInterface::handleError()} should be called
         *
         * @var bool
         */
        public bool $success = true,

        /**
         * The form object used to validate the request object
         *
         * The {@see SubmittedFormInterface::value()} will be the same as {@see RoutedRequest::$routedRequest}
         * And {@see SubmittedFormInterface::valid()} will be true if {@see RoutedRequest::$success} is true
         *
         * This value may be null if :
         * - The route has not been found or has invalid HTTP method
         * - The request represents an internal error
         * - The request is a sub-request
         * - The request has no form
         *
         * @var SubmittedFormInterface|null
         */
        public ?SubmittedFormInterface $form = null,
    ) {
    }
}
