<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Nyholm\Psr7\Stream;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function base64_encode;

class Base64ResponseMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withBody(Stream::create(base64_encode((string) $response->getBody())));
    }
}
