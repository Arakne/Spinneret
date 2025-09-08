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

/**
 * Middleware that loads the session from a cookie.
 *
 * If the cookie is found, it tries to parse it and set the user attribute on the request.
 * If the cookie is invalid or not found, a new session is created with its cookie and set on the request.
 */
final readonly class LoadSessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CookieSerializerInterface $serializer,
        private AuthenticationCookieHelper $cookieHelper,

        /**
         * The name of the cookie that will be used to store the session.
         *
         * @see CookieOptions::$name
         */
        private string $cookieName = 'auth',

        /**
         * The request attribute name where the user data will be stored.
         */
        private string $attributeName = 'user',
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var mixed $cookie */
        $cookie = $request->getCookieParams()[$this->cookieName] ?? null;
        $parsedCookie = null;
        $writeCookie = true;

        if (is_string($cookie)) {
            $parsedCookie = $this->serializer->fromString($cookie);

            if ($parsedCookie) {
                $request = $this->writeCookieToRequest($request, $parsedCookie);
                $writeCookie = false;
            }
        }

        if ($parsedCookie && $this->cookieHelper->shouldBeRefreshed($parsedCookie)) {
            $parsedCookie = $this->cookieHelper->refreshCookie($parsedCookie);
            $request = $this->writeCookieToRequest($request, $parsedCookie);
            $writeCookie = true;
        }

        if (!$parsedCookie) {
            $parsedCookie = $this->cookieHelper->createCookie();
            $request = $this->writeCookieToRequest($request, $parsedCookie);
        }

        $response = $handler->handle($request);

        if (!$writeCookie) {
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
