<?php

namespace Arakne\Spinneret\View;

use Override;

use function ob_get_clean;
use function ob_start;

/**
 * Simple view renderer implementation, allowing to use __invoke with inline HTML.
 *
 * Example:
 * ```php
 * class MyViewRenderer extends AbstractViewRenderer
 * {
 *     public function __invoke(View $view, ArticleResponse $data): void
 *     {
 *     ?>
 *         <h1><?= $data->title ?></h1>
 *         <p><?= $data->content ?></p>
 *     <?php
 *     }
 * }
 * ```
 *
 * @template D as object
 * @implements ViewRendererInterface<D>
 *
 * @method void __invoke(View $view, D $data): void
 */
abstract class AbstractViewRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        $this($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        ob_start();
        $this($view, $data);
        return ob_get_clean();
    }
}
