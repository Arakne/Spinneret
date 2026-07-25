<?php

namespace Arakne\Spinneret\Router\Attribute;

use Attribute;

/**
 * Mark a request DTO class as a GET route.
 *
 * The route will be automatically registered in the router
 * when the DTO class will be imported in the container.
 *
 * Usage:
 * ```php
 * #[Get('/path')]
 * final class MyRequest {}
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Get extends Route
{
    /**
     * @param string $path
     * @param class-string|null $fieldsExtractor
     */
    public function __construct(string $path, ?string $fieldsExtractor = null)
    {
        parent::__construct($path, ['GET'], $fieldsExtractor);
    }
}
