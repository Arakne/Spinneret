<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\View;

/**
 * @extends AbstractViewRenderer<Layout>
 */
final class LayoutRenderer extends AbstractViewRenderer
{
    public function __invoke(View $view, Layout $data): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?= $data->title ?></title>
            </head>
            <body>
                <?= $view->content ?>
            </body>
        </html>
        <?php
    }
}
