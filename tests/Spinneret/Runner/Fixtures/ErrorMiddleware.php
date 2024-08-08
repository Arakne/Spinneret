<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorMiddleware implements MiddlewareInterface
{
    #[Override] public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        throw new \Exception('Error');
    }
}
