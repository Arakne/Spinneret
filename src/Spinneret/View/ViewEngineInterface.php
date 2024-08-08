<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ResponseInterface;

/**
 * Base type for render response objects to response or string
 */
interface ViewEngineInterface
{
    /**
     * Render the response object from presenter to a PSR-7 response
     *
     * @param object $data The response data
     *
     * @return ResponseInterface The PSR-7 response
     */
    public function response(object $data): ResponseInterface;
}
