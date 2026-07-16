<?php

namespace Arakne\Spinneret\View;

use Override;

use function assert;
use function ob_end_clean;
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
 * @psalm-method void __invoke(View $view, D $data): void
 * @phpstan-method void __invoke(View $view, D $data): void
 */
abstract class AbstractViewRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        assert(is_callable($this), 'The view renderer implements method __invoke.');

        $this($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        ob_start();

        try {
            $this->display($view, $data);

            $content = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        if ($content === false) {
            throw new \RuntimeException('The output buffer has been closed unexpectedly.');
        }

        return $content;
    }
}
