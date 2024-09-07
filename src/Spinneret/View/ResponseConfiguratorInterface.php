<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ResponseInterface;

/**
 * Base type for configure the response object
 *
 * This type is called after the view rendering and before the response is sent.
 * You can implement only this interface if you just need a simple HTTP response (e.g. redirect, no content, etc.).
 *
 * @template D as object
 */
interface ResponseConfiguratorInterface
{
    /**
     * Configure the response object
     *
     * @param View $view The view context
     * @param D $data The view data
     * @param ResponseInterface $response The response object to configure
     *
     * @return ResponseInterface The configured response object
     */
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface;
}
