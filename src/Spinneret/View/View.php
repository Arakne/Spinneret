<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Store the context for the view rendering
 */
final class View
{
    /**
     * Define the parent view (i.e. layout)
     * Should be set by the renderer
     *
     * Multiple inheritance is not supported
     */
    public ?object $parent = null;

    /**
     * The original renderer view
     * Should be used by the layout renderer to render the content
     */
    public ?string $content = null;

    public function __construct(
        /**
         * The rendering response object
         */
        public readonly object $data,
        public readonly ServerRequestInterface $psrRequest,
    ) {
    }
}
