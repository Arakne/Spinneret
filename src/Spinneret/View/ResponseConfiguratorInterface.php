<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ResponseInterface;

/**
 * @template D as object
 */
interface ResponseConfiguratorInterface
{
    /**
     * @param View $view
     * @param D $data
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface;
}
