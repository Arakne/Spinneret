<?php

namespace Arakne\Tests\Spinneret\Security;

use Arakne\Spinneret\Security\AuthenticationCookieHelper;
use Arakne\Spinneret\Security\SecurityConfig;
use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class AuthenticationCookieHelperTest extends TestCase
{
    private AuthenticationCookieHelper $helper;
    private HmacCookieSerializer $serializer;

    protected function setUp(): void
    {
        $this->helper = new AuthenticationCookieHelper(
            new SecurityConfig(),
            $this->serializer = new HmacCookieSerializer(
                new ObjectUserHandler(),
                'secret',
                clock: $clock = FixedClock::instance(),
            ),
            new Randomizer(new Xoshiro256StarStar(123)),
            $clock,
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
    public function getCookieString()
    {
        $str = $this->helper->getCookieString(null);
        $this->assertSame('auth=NccrEoAwDAXAuzxdQRqSfm7TaVLVQQGG4e6AYN1e2FExipa2GDXKQxpH8dXUVHpyZhkZAR2VUhTOkbQE-F9JX8-3AYa6HXPeDw.G8Zlq9gJjrTXJ_wG0ih2fDmUXU3jUPVSih_FINNcG3Mjv_FxjSSlwxj0D2qXaq6l8TqY9d2Rll5CHO5cN1egOQ; Path=/; HttpOnly', $str);

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
        $this->assertSame('auth=Ncc9DoAgDAbQu3wzg6W2_NyG0JK4GxfC3cXB5SVv4kbFKFraYdQoD2kcxU9TU-nJmWVkBHRUSlE4R9IS4H8lfX12Awx14trSWi8.ChtxCvBMdmXJ9FcFHLmDZPkyV0dr6STd5NoNtqaJ6HbrkWu1WNxduyeGlXBGRnVcS6p9pB7BQnGNHnjX2RYFQw; Path=/; HttpOnly', $str);

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

        $this->assertSame('auth=Ncw9DoAgDAbQu3wzg1Bbfm5TKawkxrgQ7q4Ojm95ExcKepasm3n1qbNS4LabmHCNjYh7gkNF8TEwpeAlO7SfHD_eLx0MZaKP8Y6HnljrAQ.C4BHNEcki7a5iWbSdizOBSGNRArbPRWDmqYiX1uWtks5PQhQUztInLGRV50HUoTHrmIaylwiA6NmCakuzRrPHw; Path=/; HttpOnly', $str);
        $this->assertEquals($parsedCookie, $this->serializer->fromString(substr(explode(';', $str)[0], 5)));
    }

    #[Test]
    public function writeResponse()
    {
        $response = $this->helper->writeResponse(new Response(), (object) ['id' => 1]);
        $this->assertSame('auth=Ncc9DoAgDAbQu3wzg6W2_NyG0JK4GxfC3cXB5SVv4kbFKFraYdQoD2kcxU9TU-nJmWVkBHRUSlE4R9IS4H8lfX12Awx14trSWi8.ChtxCvBMdmXJ9FcFHLmDZPkyV0dr6STd5NoNtqaJ6HbrkWu1WNxduyeGlXBGRnVcS6p9pB7BQnGNHnjX2RYFQw; Path=/; HttpOnly', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function removeCookie()
    {
        $response = $this->helper->removeCookie(new Response());
        $this->assertSame('auth=; Expires=Thu, 01 Jan 1970 00:00:00 GMT', $response->getHeaderLine('Set-Cookie'));
    }
}
