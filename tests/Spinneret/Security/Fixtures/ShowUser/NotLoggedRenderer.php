<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\ShowUser;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

class NotLoggedRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        return json_encode(['success' => false, 'cookie' => $data->cookie]);
    }
}
