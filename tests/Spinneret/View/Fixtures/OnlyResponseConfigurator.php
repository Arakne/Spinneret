<?php

namespace Arakne\Tests\Spinneret\View\Fixtures;

use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Psr\Http\Message\ResponseInterface;

class OnlyResponseConfigurator implements ResponseConfiguratorInterface
{
    #[\Override] public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('X-Test', 'test')
            ->withStatus(201)
        ;
    }
}
