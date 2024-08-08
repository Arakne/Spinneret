<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

class FooErrorRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        echo json_encode(['error' => $data]);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        return json_encode(['error' => $data]);
    }
}
