<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base type for render response objects to response or string
 */
interface ViewEngineInterface
{
    /**
     * Render the response object from presenter to a PSR-7 response
     *
     * @param object $data The response data
     * @param ServerRequestInterface|null $psrRequest The PSR-7 request
     * @param object|null $routedRequest The request object parsed by the router
     *
     * @return ResponseInterface The PSR-7 response
     */
    public function response(object $data, ServerRequestInterface $psrRequest = null, ?object $routedRequest = null): ResponseInterface;

    /**
     * Render the response object from presenter to a string
     *
     * @param object $data The response data
     * @param View|null $view The view context. If null a new context is created
     *
     * @return string
     *
     * @see ViewEngineInterface::display() To display directly to the output
     */
    public function render(object $data, ?View $view = null): string;

    /**
     * Display a component view
     *
     * The content will be directly written to the output
     *
     * @param object $data The component data
     * @param View|null $view The view context. If null a new context is created
     *
     * @see ViewEngineInterface::render() To render as string instead of display
     */
    public function display(object $data, ?View $view = null): void;
}
