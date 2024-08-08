<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

class FooSuccessRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        if ($data->message === 'success view-error') {
            throw new \Exception('view error');
        }

        return json_encode(['foo' => $data]);
    }
}
