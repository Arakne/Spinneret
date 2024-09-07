<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\View;

class WithEmbeddedRenderer extends AbstractViewRenderer
{
    public function __invoke(View $view, WithEmbedded $data): void
    {
        $view->extends(new Layout('With embedded'));
        ?>
        <h1>With embedded</h1>
        <p>Some content</p>

        <?php $view->display(new EmbeddedComponent('a')); ?>
        <?= $view->render(new EmbeddedComponent('b')); ?>
        <?php
    }
}
