<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Nyholm\Psr7\Stream;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReverseMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = [];

        foreach ($request->getQueryParams() as $key => $value) {
            $params[strrev($key)] = strrev($value);
        }

        $request = $request->withQueryParams($params);
        $response = $handler->handle($request);

        return $response->withBody(Stream::create(strrev((string) $response->getBody())));
    }
}
