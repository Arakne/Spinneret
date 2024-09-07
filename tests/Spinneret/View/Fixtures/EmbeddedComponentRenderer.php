<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\View;

class EmbeddedComponentRenderer extends AbstractViewRenderer
{
    public function __invoke(View $view, EmbeddedComponent $data): void
    {
        ?>
        <div>
            <h2>Embedded component</h2>
            <p><?= $data->name ?></p>
        <?php
    }
}
