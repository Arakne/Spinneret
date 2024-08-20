<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

/**
 * @implements ViewRendererInterface<ResponseWithParent>
 */
class WithParentRenderer implements ViewRendererInterface
{
    #[Override] public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override] public function render(View $view, object $data): string
    {
        $view->parent = new Layout('My page');

        return "<p>{$data->content}</p>";
    }
}
