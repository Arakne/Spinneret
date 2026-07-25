<?php

namespace Arakne\Spinneret\View;

/**
 * Base type for do simple response body rendering
 *
 * To perform more complex operations, implement {@see ResponseConfiguratorInterface}.
 *
 * @template D as object
 */
interface ViewRendererInterface
{
    /**
     * Directly display the view to the output
     *
     * @param View $view The view context
     * @param D $data The view data
     *
     * @return void
     */
    public function display(View $view, object $data): void;

    /**
     * Render the view to a string
     *
     * @param View $view The view context
     * @param D $data The view data
     *
     * @return string
     */
    public function render(View $view, object $data): string;
}
