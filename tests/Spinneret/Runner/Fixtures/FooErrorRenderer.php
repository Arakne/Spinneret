<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;

class FooErrorRenderer implements ViewRendererInterface, ResponseConfiguratorInterface
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

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(400);
    }
}
