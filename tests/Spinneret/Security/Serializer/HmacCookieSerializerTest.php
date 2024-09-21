<?php

namespace Arakne\Tests\Spinneret\Security\Serializer;

use Arakne\Spinneret\Security\Serializer\HmacCookieSerializer;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\Security\User\ObjectUserHandler;
use Arakne\Tests\Spinneret\Stub\FixedClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HmacCookieSerializerTest extends TestCase
{
    private HmacCookieSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'secret',
            clock: FixedClock::instance(),
        );
    }

    #[Test]
    public function simple()
    {
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );

        $str = $this->serializer->toString($cookie);

        $this->assertEquals('Ncw9DoAgDAbQu3wzg1Bbfm5TKawkxrgQ7q4Ojm95ExcKepasm3n1qbNS4LabmHCNjYh7gkNF8TEwpeAlO7SfHD_eLx0MZaKP8Y6HnljrAQ.C4BHNEcki7a5iWbSdizOBSGNRArbPRWDmqYiX1uWtks5PQhQUztInLGRV50HUoTHrmIaylwiA6NmCakuzRrPHw', $str);
        $this->assertEquals($cookie, $this->serializer->fromString($str));
    }

    #[Test]
    public function disableCompression()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'secret',
            compress: false,
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );

        $str = $serializer->toString($cookie);

        $this->assertEquals('eyJ0IjoiZjk2OWEwZDFhMThmNWEzMjVlNGQ2ZDY1YzdlMzM1ZjgiLCJjIjoxNzI1MzgyMTY5LCJlIjoxNzI1Mzg1NzY5LCJ2IjoxLCJkIjp7ImZvbyI6ImJhciJ9fQ.dhu1JlegWsPRu-wiuo6f8Ju2yCvsi0iKWwmRM5eFfzMgzunu91F4RzQ-JwzFQ1vcrG3I-0NbGv5Yl1wOBlyMdg', $str);
        $this->assertEquals($cookie, $serializer->fromString($str));

        $this->assertTrue(strlen($str) > strlen($this->serializer->toString($cookie)));
    }

    #[Test]
    public function changeAlgorithm()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'secret',
            algorithm: 'sha256',
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );

        $str = $serializer->toString($cookie);

        $this->assertEquals('Ncw9DoAgDAbQu3wzg1Bbfm5TKawkxrgQ7q4Ojm95ExcKepasm3n1qbNS4LabmHCNjYh7gkNF8TEwpeAlO7SfHD_eLx0MZaKP8Y6HnljrAQ.ZPPWbEYWTnz3KaLv7TvhVNZ8UFBHP3zqBQVkmT2zJmo', $str);
        $this->assertEquals($cookie, $serializer->fromString($str));
    }

    #[Test]
    public function fromStringInvalidSecret()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'invalid',
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );
        $cookieStr = $serializer->toString($cookie);

        $this->assertNull($this->serializer->fromString($cookieStr));
    }

    #[Test]
    public function fromStringInvalidVersion()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            secret: 'secret',
            version: 2,
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 2,
            data: (object) ['foo' => 'bar'],
        );
        $cookieStr = $serializer->toString($cookie);

        $this->assertNull($this->serializer->fromString($cookieStr));
    }

    #[Test]
    public function fromStringInvalidAlgorithm()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'secret',
            algorithm: 'sha256',
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );
        $cookieStr = $serializer->toString($cookie);

        $this->assertNull($this->serializer->fromString($cookieStr));
    }

    #[Test]
    public function fromStringInvalidCompression()
    {
        $serializer = new HmacCookieSerializer(
            new ObjectUserHandler(),
            'secret',
            compress: false,
            clock: FixedClock::instance(),
        );
        $cookie = new ParsedCookie(
            token: 'f969a0d1a18f5a325e4d6d65c7e335f8',
            creation: FixedClock::instance()->now()->getTimestamp(),
            expiration: FixedClock::instance()->now()->getTimestamp() + 3600,
            version: 1,
            data: (object) ['foo' => 'bar'],
        );
        $cookieStr = $serializer->toString($cookie);

        $this->assertNull($this->serializer->fromString($cookieStr));
    }

    #[Test]
    public function fromStringInvalid()
    {
        $this->assertNull($this->serializer->fromString('missingPart'));
        $this->assertNull($this->serializer->fromString('###.firstNotBase64'));
        $this->assertNull($this->serializer->fromString('secondNotBase64.###'));

        $data = 'notDeflate';
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate('not json');
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate('"not array"');
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['foo' => 'missing_keys']));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['t' => true, 'c' => 1, 'e' => 1, 'v' => 1, 'd' => null]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['t' => 'a', 'c' => 1, 'e' => '1', 'v' => 1, 'd' => null]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['t' => 'a', 'c' => 1, 'e' => 1, 'v' => '1', 'd' => null]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['t' => 'a', 'c' => 1, 'e' => FixedClock::instance()->now()->getTimestamp() - 1, 'v' => 1, 'd' => []]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        $data = gzdeflate(json_encode(['t' => 'a', 'c' => FixedClock::instance()->now()->getTimestamp() + 1, 'e' => FixedClock::instance()->now()->getTimestamp() + 1, 'v' => 1, 'd' => []]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));

        // Valid exemple
        $data = gzdeflate(json_encode(['t' => 'a', 'c' => 1, 'e' => FixedClock::instance()->now()->getTimestamp() + 1, 'v' => 1, 'd' => null]));
        $signature = hash_hmac('sha512', $data, 'secret', true);
        $this->assertNotNull($this->serializer->fromString(base64_encode($data) . '.' . base64_encode($signature)));
    }
}
