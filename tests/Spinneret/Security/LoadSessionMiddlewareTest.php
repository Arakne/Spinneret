<?php

namespace Arakne\Tests\Spinneret\Security;

use Arakne\Spinneret\Security\AuthenticationCookieHelper;
use Arakne\Spinneret\Security\LoadSessionMiddleware;
use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Tests\Spinneret\Security\Fixtures\TestSecurityApplication;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUserHandler;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

use function var_dump;

class LoadSessionMiddlewareTest extends TestCase
{
    #[Test]
    public function notLoggedShouldCreateAnonymousSession()
    {
        $app = new TestSecurityApplication(isDev: true);

        $req = new ServerRequest('GET', '/user');
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => false,
            'cookie' => [
                'token' => 'f969a0d1a18f5a325e4d6d65c7e335f8',
                'creation' => 1725382169,
                'expiration' => 1725385769,
                'version' => 1,
                'data' => null,
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');

        $this->assertEquals('auth=NccrEoAwDAXAuzxdQRqSfm7TaVLVQQGG4e6AYN1e2FExipa2GDXKQxpH8dXUVHpyZhkZAR2VUhTOkbQE-F9JX8-3AYa6HXPeDw.RPtAk4hAC43r13-TBot18jbtQ35MiLrtX8uR90XLUWbrNWTOLLKEdsfHheZP0K4XwLqdhtut163VXUovV7giLQ; Path=/; HttpOnly', $cookie);
    }

    #[Test]
    public function withAnonymousSession()
    {
        $app = new TestSecurityApplication(isDev: true);
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'my_secret',
            clock: FixedClock::instance(),
        );
        $cookie = $serializer->toString($parsedCookie = new ParsedCookie(
            token: 'my_token',
            creation: FixedClock::instance()->now()->getTimestamp() - 250,
            expiration: FixedClock::instance()->now()->getTimestamp() + 3000,
            version: 1,
            data: null,
        ));

        $req = new ServerRequest('GET', '/user');
        $req = $req->withCookieParams(['auth' => $cookie]);
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => false,
            'cookie' => [
                'token' => $parsedCookie->token,
                'creation' => $parsedCookie->creation,
                'expiration' => $parsedCookie->expiration,
                'version' => 1,
                'data' => null,
            ]
        ], json_decode((string) $response->getBody(), true));

        $this->assertEmpty($response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function login()
    {
        $app = new TestSecurityApplication(isDev: true);

        $req = new ServerRequest('POST', '/login');
        $req = $req->withParsedBody([
            'username' => 'admin',
            'password' => 'very_secure',
        ]);
        $response = $app->handle($req);

        $this->assertEquals([
            'authenticatedUser' => [
                'username' => 'admin',
                'password' => 'very_secure',
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertEquals('auth=Ncs7DsIwFETRvUztwpkQ_zaDHs6zRJGAbBKEouwdU1Ae3ZkDLySwiCvM48XxJuK9dYG5iMRoGUgLg4w0eE5j4OCigf45-R_3ToMZ6cDWtK6y9AFkXu5r_z6ltfej9oxd6-faNG9VcZ5f.ysHu2CdjZqT7QUtfJ8d30uc7rn-5rSUA-SbevdBCw8Cm8CRDeRhBUJWm_DEjfmoe8R7IjKfp9wEr7ucuG8SifQ; Path=/; HttpOnly', $cookie);

        $token = explode('=', explode(';', $cookie, 2)[0], 2)[1];

        $req = new ServerRequest('GET', '/user');
        $req = $req->withCookieParams(['auth' => $token]);
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => true,
            'user' => [
                'username' => 'admin',
                'password' => 'very_secure',
            ]
        ], json_decode((string) $response->getBody(), true));
        $this->assertEmpty($response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function shouldCreateCookieIfNameDoesntMatchExactly()
    {
        $middleware = new LoadSessionMiddleware(
            $serializer = new HmacCookieSerializer(
                new TestUserHandler(),
                secret: 'my_secret',
                clock: FixedClock::instance(),
            ),
            new AuthenticationCookieHelper(
                new SecurityConfig(),
                $serializer,
                new Randomizer(new Xoshiro256StarStar(123)),
                FixedClock::instance(),
            ),
            cookieName: 'auth',
            attributeName: 'user',
        );

        $req = new ServerRequest('POST', '/login');
        $req = $req->withParsedBody([
            'username' => 'admin',
            'password' => 'very_secure',
        ]);

        $response = $middleware->process($req, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withAddedHeader('Set-Cookie', 'auth2=foo');
            }
        });

        $cookies = $response->getHeader('Set-Cookie');
        $this->assertEquals([
            'auth2=foo',
            'auth=NccrEoAwDAXAuzxdQRqSfm7TaVLVQQGG4e6AYN1e2FExipa2GDXKQxpH8dXUVHpyZhkZAR2VUhTOkbQE-F9JX8-3AYa6HXPeDw.RPtAk4hAC43r13-TBot18jbtQ35MiLrtX8uR90XLUWbrNWTOLLKEdsfHheZP0K4XwLqdhtut163VXUovV7giLQ; Path=/; HttpOnly',
        ], $cookies);
    }

    #[Test]
    public function shouldNotCreateCookieIfNameMatchExactly()
    {
        $middleware = new LoadSessionMiddleware(
            $serializer = new HmacCookieSerializer(
                new TestUserHandler(),
                secret: 'my_secret',
                clock: FixedClock::instance(),
            ),
            new AuthenticationCookieHelper(
                new SecurityConfig(),
                $serializer,
                new Randomizer(new Xoshiro256StarStar(123)),
                FixedClock::instance(),
            ),
            cookieName: 'auth',
            attributeName: 'user',
        );

        $req = new ServerRequest('POST', '/login');
        $req = $req->withParsedBody([
            'username' => 'admin',
            'password' => 'very_secure',
        ]);

        $response = $middleware->process($req, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withAddedHeader('Set-Cookie', 'auth=foo');
            }
        });

        $cookies = $response->getHeader('Set-Cookie');
        $this->assertEquals([
            'auth=foo',
        ], $cookies);
    }

    #[Test]
    public function withExpiredSessionShouldBeRecreated()
    {
        $app = new TestSecurityApplication(isDev: true);
        $serializer = new HmacCookieSerializer(
            new TestUserHandler(),
            'my_secret',
            clock: FixedClock::instance(),
        );
        $cookie = $serializer->toString(new ParsedCookie(
            token: 'my_token',
            creation: FixedClock::instance()->now()->getTimestamp() - 5000,
            expiration: FixedClock::instance()->now()->getTimestamp() - 200,
            version: 1,
            data: new TestUser('admin', 'very_secure'),
        ));

        $req = new ServerRequest('GET', '/user');
        $req = $req->withCookieParams(['auth' => $cookie]);
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => false,
            'cookie' => [
                'token' => 'f969a0d1a18f5a325e4d6d65c7e335f8',
                'creation' => 1725382169,
                'expiration' => 1725385769,
                'version' => 1,
                'data' => null,
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');

        $this->assertEquals('auth=NccrEoAwDAXAuzxdQRqSfm7TaVLVQQGG4e6AYN1e2FExipa2GDXKQxpH8dXUVHpyZhkZAR2VUhTOkbQE-F9JX8-3AYa6HXPeDw.RPtAk4hAC43r13-TBot18jbtQ35MiLrtX8uR90XLUWbrNWTOLLKEdsfHheZP0K4XwLqdhtut163VXUovV7giLQ; Path=/; HttpOnly', $cookie);
    }
}
