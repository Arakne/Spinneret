<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_string;

final readonly class LoadUserMiddleware implements MiddlewareInterface
{
    public const string ATTRIBUTE_NAME = 'user';

    public function __construct(
        private CookieSerializerInterface $serializer,
        private string $cookieName = 'auth',
        private string $attributeName = self::ATTRIBUTE_NAME,
    ) {
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams()[$this->cookieName] ?? null;

        if (is_string($cookie)) {
            $parsedCookie = $this->serializer->fromCookie($cookie);

            if ($parsedCookie) {
                $request = $request
                    ->withAttribute($this->cookieName, $parsedCookie)
                    ->withAttribute($this->attributeName, $parsedCookie->data)
                ;
            }
        }

        // @todo refresh cookie

        return $handler->handle($request);
    }
}
