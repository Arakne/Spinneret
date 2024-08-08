<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;

/**
 * @implements ViewRendererInterface<OtherResponse>
 */
class RendererWithResponseConfigurator implements ViewRendererInterface, ResponseConfiguratorInterface
{
    #[Override] public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override] public function render(View $view, object $data): string
    {
        return "<p>{$data->content}</p>";
    }

    #[Override] public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('X-Test', 'test')
            ->withStatus(201)
        ;
    }
}
