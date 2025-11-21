<?php

namespace Arakne\Tests\Spinneret\Security;

use Arakne\Spinneret\Security\AuthenticationCookieHelper;
use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUserHandler;
use DateTimeImmutable;
use Nyholm\Psr7\Response;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class AuthenticationCookieHelperTest extends TestCase
{
    private AuthenticationCookieHelper $helper;
    private HmacCookieSerializer $serializer;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->helper = new AuthenticationCookieHelper(
            new SecurityConfig(
                ttl: 3600,
                refreshThreshold: 30,
                extendExpiration: true,
            ),
            $this->serializer = new HmacCookieSerializer(
                $userHandler = new ObjectUserHandler(),
                'secret',
                clock: $this->clock = new class implements ClockInterface {
                    public function __construct(
                        public DateTimeImmutable $now = new DateTimeImmutable('2024-09-03T18:49:29+02'),
                    ) {}

                    #[Override]
                    public function now(): DateTimeImmutable
                    {
                        return $this->now;
                    }

                    public function advance(string $time): void
                    {
                        $this->now = $this->now->modify($time);
                    }
                },
            ),
            $userHandler,
            new Randomizer(new Xoshiro256StarStar(123)),
            $this->clock,
        );
    }

    #[Test]
    public function createCookie()
    {
        $cookie = $this->helper->createCookie();

        $this->assertSame('f969a0d1a18f5a325e4d6d65c7e335f8', $cookie->token);
        $this->assertSame(1725382169, $cookie->creation);
        $this->assertSame(1725382169 + 3600, $cookie->expiration);
        $this->assertSame(1, $cookie->version);
        $this->assertNull($cookie->data);

        $otherCookie = $this->helper->createCookie();

        $this->assertNotSame($cookie->token, $otherCookie->token);
        $this->assertSame(1725382169, $otherCookie->creation);
        $this->assertSame(1725382169 + 3600, $otherCookie->expiration);
        $this->assertSame(1, $otherCookie->version);
        $this->assertNull($otherCookie->data);

        $withData = $this->helper->createCookie($d = (object) ['id' => 1]);

        $this->assertNotSame($cookie->token, $withData->token);
        $this->assertSame('de789d95b3d878566a28295af8ebf9ff', $withData->token);
        $this->assertSame(1725382169, $withData->creation);
        $this->assertSame(1725382169 + 3600, $withData->expiration);
        $this->assertSame(1, $withData->version);
        $this->assertSame($d, $withData->data);
    }

    #[Test]
    public function shouldBeRefreshed()
    {
        $cookie = $this->helper->createCookie();
        $this->assertFalse($this->helper->shouldBeRefreshed($cookie));

        $this->clock->advance('+29seconds');
        $this->assertFalse($this->helper->shouldBeRefreshed($cookie));

        $this->clock->advance('+2seconds');
        $this->assertTrue($this->helper->shouldBeRefreshed($cookie));
    }

    #[Test]
    public function refreshCookieWithExtendExpiration()
    {
        $helper = new AuthenticationCookieHelper(
            new SecurityConfig(
                ttl: 3600,
                refreshThreshold: 30,
                extendExpiration: true,
            ),
            new HmacCookieSerializer(
                $userHandler = new TestUserHandler(),
                'secret',
                clock: $this->clock,
            ),
            $userHandler,
            new Randomizer(new Xoshiro256StarStar(123)),
            $this->clock,
        );

        $cookie = $helper->createCookie(new TestUser('foo', 'bar'));
        $this->clock->advance('+35seconds');

        $refreshed = $helper->refreshCookie($cookie);
        $this->assertFalse($helper->shouldBeRefreshed($refreshed));
        $this->assertSame($cookie->creation, $refreshed->creation);
        $this->assertSame($this->clock->now()->getTimestamp(), $refreshed->refresh);
        $this->assertSame($this->clock->now()->getTimestamp() + 3600, $refreshed->expiration);
        $this->assertSame($cookie->version, $refreshed->version);
        $this->assertEquals(new TestUser('foo', 'bar', 1), $refreshed->data);

        $refreshed = $helper->refreshCookie($refreshed);
        $refreshed = $helper->refreshCookie($refreshed);

        $this->assertNull($refreshed->data);

        $refreshed = $helper->refreshCookie($refreshed);
        $this->assertNull($refreshed->data);
    }

    #[Test]
    public function refreshCookieWithoutExtendExpiration()
    {
        $helper = new AuthenticationCookieHelper(
            new SecurityConfig(
                ttl: 3600,
                refreshThreshold: 30,
                extendExpiration: false,
            ),
            new HmacCookieSerializer(
                $userHandler = new TestUserHandler(),
                'secret',
                clock: $this->clock,
            ),
            $userHandler,
            new Randomizer(new Xoshiro256StarStar(123)),
            $this->clock,
        );

        $cookie = $helper->createCookie(new TestUser('foo', 'bar'));
        $this->clock->advance('+35seconds');

        $refreshed = $helper->refreshCookie($cookie);
        $this->assertFalse($helper->shouldBeRefreshed($refreshed));
        $this->assertSame($cookie->creation, $refreshed->creation);
        $this->assertSame($this->clock->now()->getTimestamp(), $refreshed->refresh);
        $this->assertSame($cookie->expiration, $refreshed->expiration);
        $this->assertSame($cookie->version, $refreshed->version);
        $this->assertEquals(new TestUser('foo', 'bar', 1), $refreshed->data);

        $refreshed = $helper->refreshCookie($refreshed);
        $refreshed = $helper->refreshCookie($refreshed);

        $this->assertNull($refreshed->data);

        $refreshed = $helper->refreshCookie($refreshed);
        $this->assertNull($refreshed->data);
    }

    #[Test]
    public function getCookieString()
    {
        $str = $this->helper->getCookieString(null);
        $this->assertSame('auth=VccrEoAwDAXAuzxdQRqSfm7TaVrVQTCAYbg7IBCs2xMbMnrSVCajQrFLYS9tNjWVGhqz9AiHikzBC0dPmhzWf9tXCW-Ppw6GvOxjXDc.-Wk34EusU4zE_NSMYfx9xEoaJMFtq94hHAKyiZb6ZeuD4wjWOMeAaHXwYHBXJ8g0gEhs_Ncuw_oLPy1VRSvXJw; Path=/; HttpOnly', $str);

        $parsedCookie = $this->serializer->fromString(substr(explode(';', $str)[0], 5));
        $this->assertSame('f969a0d1a18f5a325e4d6d65c7e335f8', $parsedCookie->token);
        $this->assertSame(1725382169, $parsedCookie->creation);
        $this->assertSame(1725382169 + 3600, $parsedCookie->expiration);
        $this->assertSame(1, $parsedCookie->version);
        $this->assertNull($parsedCookie->data);
    }

    #[Test]
    public function getCookieStringWithData()
    {
        $str = $this->helper->getCookieString($d = (object) ['id' => 1]);
        $this->assertSame('auth=Vcc9DoAgDAbQu3wzg6W2_NyGUEhcjXEh3F0cHFxe8gYuZPSkqWxGhWKXwl7abmoqNTRm6REOFZmCF46eNDmc_7avEt7eqw6GPHAsac4H.ecgj6nt8gE4wNdK2m5fWXNzVUznDVbKUDB6xtMegWNYv1U_-oWIacLoUv1D3GBJVYyvu24tktMg0zTpsg1HNVw; Path=/; HttpOnly', $str);

        $parsedCookie = $this->serializer->fromString(substr(explode(';', $str)[0], 5));
        $this->assertSame('f969a0d1a18f5a325e4d6d65c7e335f8', $parsedCookie->token);
        $this->assertSame(1725382169, $parsedCookie->creation);
        $this->assertSame(1725382169 + 3600, $parsedCookie->expiration);
        $this->assertSame(1, $parsedCookie->version);
        $this->assertEquals($d, $parsedCookie->data);
    }

    #[Test]
    public function getCookieStringWithParsedCookieAsParameter()
    {
        $parsedCookie = $this->helper->createCookie((object) ['foo' => 'bar']);

        $str = $this->helper->getCookieString($parsedCookie);

        $this->assertSame('auth=Vc07DoAgEAXAu7yawmVdfrdBYFsSYmwId1cLC8upZuJEgkYX81YpU1DJbKXt1VUnxTdm0QCDgkTeCgdLLhqMP9tH8S-vhwYVaUJ7f4IjD6x1Aw.7ish2c2K0ZVaFY1ZZSMPN7o4ZJ9BK8DBsORI8xYM-cl925flogmEoCe0e3750KQ3d5MHVbH2uMChffUTfDKtZw; Path=/; HttpOnly', $str);
        $this->assertEquals($parsedCookie, $this->serializer->fromString(substr(explode(';', $str)[0], 5)));
    }

    #[Test]
    public function writeResponse()
    {
        $response = $this->helper->writeResponse(new Response(), (object) ['id' => 1]);
        $this->assertSame('auth=Vcc9DoAgDAbQu3wzg6W2_NyGUEhcjXEh3F0cHFxe8gYuZPSkqWxGhWKXwl7abmoqNTRm6REOFZmCF46eNDmc_7avEt7eqw6GPHAsac4H.ecgj6nt8gE4wNdK2m5fWXNzVUznDVbKUDB6xtMegWNYv1U_-oWIacLoUv1D3GBJVYyvu24tktMg0zTpsg1HNVw; Path=/; HttpOnly', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function removeCookie()
    {
        $response = $this->helper->removeCookie(new Response());
        $this->assertSame('auth=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/;', $response->getHeaderLine('Set-Cookie'));
    }
}
