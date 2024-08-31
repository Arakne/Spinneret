<?php

namespace Arakne\Spinneret\Security;

use Arakne\Spinneret\Security\Serializer\CookieSerializerInterface;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_string;
use function str_starts_with;

// @todo rename : un cookie sera toujours chargé, mais pas forcément un utilisateur
final readonly class LoadUserMiddleware implements MiddlewareInterface
{
    public const string ATTRIBUTE_NAME = 'user';

    public function __construct(
        private CookieSerializerInterface $serializer,
        private AuthenticationCookieHelper $cookieHelper,
        private string $cookieName = 'auth',
        private string $attributeName = self::ATTRIBUTE_NAME,
    ) {
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams()[$this->cookieName] ?? null;
        $parsedCookie = null;
        $hasCookie = false;

        if (is_string($cookie)) {
            $parsedCookie = $this->serializer->fromString($cookie);

            if ($parsedCookie) {
                $request = $this->writeCookieToRequest($request, $parsedCookie);
                $hasCookie = true;
            }
        }

        if (!$parsedCookie) {
            $parsedCookie = $this->cookieHelper->createCookie();
            $request = $this->writeCookieToRequest($request, $parsedCookie);
        }

        // @todo refresh cookie

        $response = $handler->handle($request);

        if ($hasCookie) {
            return $response;
        }

        // A new cookie has already been set, so no need to add another one
        foreach ($response->getHeader('Set-Cookie') as $header) {
            if (str_starts_with($header, $this->cookieName.'=')) {
                return $response;
            }
        }

        return $this->cookieHelper->writeResponse($response, $parsedCookie);
    }

    private function writeCookieToRequest(ServerRequestInterface $request, ParsedCookie $cookie): ServerRequestInterface
    {
        return $request
            ->withAttribute(ParsedCookie::class, $cookie)
            ->withAttribute($this->cookieName, $cookie)
            ->withAttribute($this->attributeName, $cookie->data)
        ;
    }
}
