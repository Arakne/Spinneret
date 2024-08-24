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
     * @param ServerRequestInterface $psrRequest The PSR-7 request
     * @param object $data The response data
     *
     * @return ResponseInterface The PSR-7 response
     */
    public function response(ServerRequestInterface $psrRequest, object $data): ResponseInterface;

    /**
     * Render the response object from presenter to a string
     *
     * @param object $data The response data
     * @param View|null $view The view context. If null a new context is created
     *
     * @return string
     */
    public function render(object $data, ?View $view = null): string;

    /**
     * Display a component view
     *
     * The content should be directly written to the output
     *
     * @param View $view The view context.
     * @param object $data The component data
     */
    public function display(View $view, object $data): void;
}
