<?php

namespace Arakne\Tests\Spinneret\Form\Csrf;

use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Tests\Spinneret\Form\Fixtures\BasicCsrfForm;
use Arakne\Tests\Spinneret\Form\Fixtures\OtherCsrfForm;
use ArrayObject;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use ReflectionProperty;

use function array_keys;

class CsrfHelperTest extends TestCase
{
    private CsrfHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CsrfHelper(DefaultFormFactory::runtime());
    }

    #[Test]
    public function setCsrf()
    {
        $r = new BasicCsrfForm();
        $o = new OtherCsrfForm();
        $psr = new ServerRequest('POST', 'http://localhost/csrf');
        $psr = $psr->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            refresh: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $this->assertSame($r, $this->helper->setCsrf($r, $psr));
        $this->assertInstanceOf(CsrfTokenParameters::class, $r->csrf);
        $this->assertSame('ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3', $r->csrf->token());

        $this->assertSame($o, $this->helper->setCsrf($o, $psr));
        $this->assertInstanceOf(CsrfTokenParameters::class, $o->other);
        $this->assertSame('9c83003eecf27743c467e704876d53b098178ab7c25f51765c11f7c1e685bffe', $o->other->token());
        $this->assertInstanceOf(CsrfTokenParameters::class, $o->csrf);
        $this->assertSame('6f9ab287c99556813f109ddab1985bed8761bb3b4fc2a57f91d1085927b44626', $o->csrf->token());

        $psr = $psr->withAttribute(ParsedCookie::class, '');
        $r = new BasicCsrfForm();
        $this->assertSame($r, $this->helper->setCsrf($r, $psr));
        $this->assertFalse(isset($r->csrf));

        $cacheField = new ReflectionProperty(CsrfHelper::class, 'cache');
        $this->assertSame([BasicCsrfForm::class, OtherCsrfForm::class], array_keys($cacheField->getValue($this->helper)));
    }

    #[Test]
    public function getCsrfToken()
    {
        $psr = new ServerRequest('POST', 'http://localhost/csrf');
        $psr = $psr->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            refresh: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $this->assertSame('ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3', $this->helper->getCsrfToken(BasicCsrfForm::class, $psr));
        $this->assertSame('6f9ab287c99556813f109ddab1985bed8761bb3b4fc2a57f91d1085927b44626', $this->helper->getCsrfToken(OtherCsrfForm::class, $psr));
        $this->assertNull($this->helper->getCsrfToken(ArrayObject::class, $psr));
    }

    #[Test]
    public function getCsrfFields()
    {
        $psr = new ServerRequest('POST', 'http://localhost/csrf');
        $psr = $psr->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            refresh: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $this->assertSame(['csrf' => 'ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3'], $this->helper->getCsrfFields(BasicCsrfForm::class, $psr));
        $this->assertSame([
            'csrf' => '6f9ab287c99556813f109ddab1985bed8761bb3b4fc2a57f91d1085927b44626',
            'other' => '9c83003eecf27743c467e704876d53b098178ab7c25f51765c11f7c1e685bffe',
        ], $this->helper->getCsrfFields(OtherCsrfForm::class, $psr));
        $this->assertNull($this->helper->getCsrfToken(ArrayObject::class, $psr));
    }

    #[Test]
    public function form()
    {
        $req = new ServerRequest('POST', 'http://localhost/csrf');
        $req = $req->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            refresh: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $form = $this->helper->form(BasicCsrfForm::class, $req);
        $this->assertSame('ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3', $form->view()['csrf']->value);
    }
}
