<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;

/**
 * @implements ViewRendererInterface<InternalServerError>
 */
class InternalServerErrorRenderer implements ViewRendererInterface
{
    #[Override] public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override] public function render(View $view, object $data): string
    {
        return json_encode([
            'error' => $data->error::class . ' : ' . $data->error->getMessage(),
            'step' => $data->step->name,
            'request' => $data->request,
        ]);
    }
}
