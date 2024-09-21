<?php

namespace Arakne\Tests\Spinneret\Security;

use Arakne\Spinneret\Security\CookieOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CookieOptionsTest extends TestCase
{
    #[Test]
    public function format()
    {
        $this->assertSame('auth=foo; HttpOnly', (new CookieOptions(path: null))->format('foo'));
        $this->assertSame('auth=foo; Path=/; HttpOnly', (new CookieOptions(path: '/'))->format('foo'));
        $this->assertSame('auth=foo; Path=/; Domain=example.com; HttpOnly', (new CookieOptions(domain: 'example.com'))->format('foo'));
        $this->assertSame('auth=foo; Path=/; Secure; HttpOnly', (new CookieOptions(secure: true))->format('foo'));
        $this->assertSame('auth=foo; Path=/; HttpOnly; SameSite=Lax', (new CookieOptions(sameSite: CookieOptions::SAME_SITE_LAX))->format('foo'));
        $this->assertSame('auth=foo; Path=/; HttpOnly; Max-Age=3600', (new CookieOptions(maxAge: 3600))->format('foo'));
        $this->assertSame('auth=foo; Path=/', (new CookieOptions(httpOnly: false))->format('foo'));
        $this->assertSame('other=foo; Path=/; HttpOnly', (new CookieOptions(name: 'other'))->format('foo'));
    }
}
