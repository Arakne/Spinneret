<?php

namespace Arakne\Spinneret\View;

/**
 * @template D as object
 * @todo interface or method for configure response object
 */
interface ViewRendererInterface
{
    /**
     * @param View $view
     * @param D $data
     * @return void
     */
    public function display(View $view, object $data): void;

    /**
     * @param View $view
     * @param D $data
     * @return string
     */
    public function render(View $view, object $data): string;
}
