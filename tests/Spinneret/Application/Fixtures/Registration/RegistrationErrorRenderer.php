<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RegistrationErrorRenderer implements ViewRendererInterface, ResponseConfiguratorInterface
{
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(400);
    }

    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    public function render(View $view, object $data): string
    {
        return json_encode(['error' => $data]);
    }
}
