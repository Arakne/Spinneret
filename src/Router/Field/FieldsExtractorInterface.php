<?php

namespace Arakne\Spinneret\Router\Field;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Base type for extract fields from PSR-7 server request before form submission.
 * This type is a functor that will extract fields from the request, and will be used as default fields extractor on the router.
 *
 * All implementations must take the request class name as constructor argument.
 */
interface FieldsExtractorInterface
{
    /**
     * @param class-string $requestClassName
     */
    public function __construct(string $requestClassName);

    /**
     * Extract fields from the request
     *
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    public function __invoke(ServerRequestInterface $request): array;
}
