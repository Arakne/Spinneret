<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

final readonly class ShowConfigRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        return json_encode($data);
    }
}
