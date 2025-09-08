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
                'refresh' => 1725382169,
                'expiration' => 1725385769,
                'version' => 1,
                'data' => null,
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');

        $this->assertEquals('auth=VccrEoAwDAXAuzxdQRqSfm7TaVrVQTCAYbg7IBCs2xMbMnrSVCajQrFLYS9tNjWVGhqz9AiHikzBC0dPmhzWf9tXCW-Ppw6GvOxjXDc.bWX3--pDK1lc1fnJW4BqTV7EApiYzZPREjyDrxwsInEU2NeJ_eLRoLcQH3Gxu4VN913i-LV8aSWRWVb5BkohBg; Path=/; HttpOnly', $cookie);
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
            creation: $created = FixedClock::instance()->now()->getTimestamp() - 250,
            refresh: $created,
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
                'refresh' => $parsedCookie->creation,
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
                'refresh' => 0,
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertEquals('auth=Vcw7DoMwEIThu0ztwizBr8tEG7OWUvDQOhAhxN3jFClSfvpHc-KFBCrsCuX-5ujB7L11gXJhjtFSILIwyEidp6EP1LlooP-UHwf_5d5oMCKd2KrozFMbgMfpOberlWt9L9oydtHjXiVvKriuDw.qYU0mSmiuBs80eaIyQCdDiYsEY22LcOB1IcpN-j_wt_Eu0ntQN1yiJO0Us3oJW2rtXIyJV1UrcjxdJFtA-GckA; Path=/; HttpOnly', $cookie);

        $token = explode('=', explode(';', $cookie, 2)[0], 2)[1];

        $req = new ServerRequest('GET', '/user');
        $req = $req->withCookieParams(['auth' => $token]);
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => true,
            'user' => [
                'username' => 'admin',
                'password' => 'very_secure',
                'refresh' => 0,
            ]
        ], json_decode((string) $response->getBody(), true));
        $this->assertEmpty($response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function shouldCreateCookieIfNameDoesntMatchExactly()
    {
        $middleware = new LoadSessionMiddleware(
            $serializer = new HmacCookieSerializer(
                $userHandler = new TestUserHandler(),
                secret: 'my_secret',
                clock: FixedClock::instance(),
            ),
            new AuthenticationCookieHelper(
                new SecurityConfig(),
                $serializer,
                $userHandler,
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
            'auth=VccrEoAwDAXAuzxdQRqSfm7TaVrVQTCAYbg7IBCs2xMbMnrSVCajQrFLYS9tNjWVGhqz9AiHikzBC0dPmhzWf9tXCW-Ppw6GvOxjXDc.bWX3--pDK1lc1fnJW4BqTV7EApiYzZPREjyDrxwsInEU2NeJ_eLRoLcQH3Gxu4VN913i-LV8aSWRWVb5BkohBg; Path=/; HttpOnly',
        ], $cookies);
    }

    #[Test]
    public function shouldNotCreateCookieIfNameMatchExactly()
    {
        $middleware = new LoadSessionMiddleware(
            $serializer = new HmacCookieSerializer(
                $userHandler = new TestUserHandler(),
                secret: 'my_secret',
                clock: FixedClock::instance(),
            ),
            new AuthenticationCookieHelper(
                new SecurityConfig(),
                $serializer,
                $userHandler,
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
            creation: $created = FixedClock::instance()->now()->getTimestamp() - 5000,
            refresh: $created,
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
                'refresh' => 1725382169,
                'expiration' => 1725385769,
                'version' => 1,
                'data' => null,
            ]
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');

        $this->assertEquals('auth=VccrEoAwDAXAuzxdQRqSfm7TaVrVQTCAYbg7IBCs2xMbMnrSVCajQrFLYS9tNjWVGhqz9AiHikzBC0dPmhzWf9tXCW-Ppw6GvOxjXDc.bWX3--pDK1lc1fnJW4BqTV7EApiYzZPREjyDrxwsInEU2NeJ_eLRoLcQH3Gxu4VN913i-LV8aSWRWVb5BkohBg; Path=/; HttpOnly', $cookie);
    }

    #[Test]
    public function shouldRefreshSession()
    {
        $app = new TestSecurityApplication(isDev: true);
        $serializer = new HmacCookieSerializer(
            new TestUserHandler(),
            'my_secret',
            clock: FixedClock::instance(),
        );
        $cookie = $serializer->toString(new ParsedCookie(
            token: 'my_token',
            creation: $created = FixedClock::instance()->now()->getTimestamp() - 5000,
            refresh: $created + 4000,
            expiration: FixedClock::instance()->now()->getTimestamp() + 500,
            version: 1,
            data: new TestUser('admin', 'very_secure'),
        ));

        $req = new ServerRequest('GET', '/user');
        $req = $req->withCookieParams(['auth' => $cookie]);
        $response = $app->handle($req);

        $this->assertEquals([
            'success' => true,
            'user' => [
                'username' => 'admin',
                'password' => 'very_secure',
                'refresh' => 1,
            ],
        ], json_decode((string) $response->getBody(), true));

        $cookie = $response->getHeaderLine('Set-Cookie');

        $this->assertEquals('auth=NcxBCoAgFIThu8zaTYVZXibE3iLCimcaId49jVp-_MMknNBw93TuK20QsNCNamWnVNOPAvxxaF_ST6kqY6HADJ0QPPFmXBnAzG6pV4fx_tq5ZETie_JkAxNyfgA.htAFhrXb68awq9pR1L1jXc5mp4ntvNRkXDHiibVEwfAdBN_4h51ijfzijoPjqipg9rdKNNJ7nSj6VKHDEddPtw; Path=/; HttpOnly', $cookie);
    }
}
