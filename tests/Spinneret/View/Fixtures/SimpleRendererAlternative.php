<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

/**
 * @implements ViewRendererInterface<SimpleResponse>
 */
class SimpleRendererAlternative implements ViewRendererInterface
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

        return "<blockquote>{$data->content}</blockquote>";
    }
}
