<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\View;

class WithTranslationRenderer extends AbstractViewRenderer
{
    public function __invoke(View $view, object $data): void
    {
        ?>
        <h1><?= $view->_('With translations') ?></h1>

        <p><?= $view->_('This page is translated') ?></p>
        <div><?= $view->_('Author: {name}', ['{name}' => 'John Doe']) ?></div>
        <?php
    }
}
