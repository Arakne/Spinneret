<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;

final readonly class RegistrationSuccessRenderer implements ViewRendererInterface
{
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    public function render(View $view, object $data): string
    {
        return json_encode(['success' => $data]);
    }
}
