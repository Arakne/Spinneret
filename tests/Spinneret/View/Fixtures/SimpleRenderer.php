<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

/**
 * @implements ViewRendererInterface<SimpleResponse>
 */
class SimpleRenderer implements ViewRendererInterface
{
    public View $view;
    public object $data;

    #[Override] public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override] public function render(View $view, object $data): string
    {
        $this->view = $view;
        $this->data = $data;

        return "<p>{$data->content}</p>";
    }
}
