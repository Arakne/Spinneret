<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolve the theme to select the corresponding renderer, if any
 */
interface ViewThemeResolverInterface
{
    /**
     * Resolve the theme ID from the given data object and request
     *
     * @param object $data The response DTO object to render
     * @param ServerRequestInterface|null $request The current PSR-7 server request
     *
     * @return string|null The theme ID, or null if default theme should be used
     */
    public function resolveThemeId(object $data, ?ServerRequestInterface $request): ?string;
}
