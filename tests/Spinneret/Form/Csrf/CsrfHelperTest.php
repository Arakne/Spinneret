<?php

namespace Arakne\Tests\Spinneret\Form\Csrf;

use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Tests\Spinneret\Form\Fixtures\BasicCsrfForm;
use Arakne\Tests\Spinneret\Form\Fixtures\OtherCsrfForm;
use Arakne\Tests\Spinneret\Form\Fixtures\SimpleFormWithCsrf;
use ArrayObject;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;

class CsrfHelperTest extends TestCase
{
    private CsrfHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CsrfHelper(DefaultFormFactory::runtime());
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

    #[Test]
    public function formWithData()
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

        $form = $this->helper->form(SimpleFormWithCsrf::class, $req, ['foo' => 'aaa']);
        $this->assertSame('10e259a99fbdd960248a2693f41eaa868bcaa5343ba15d1c836e774970c82c59', $form->view()['csrf']->value);
        $this->assertSame('aaa', $form->view()['foo']->value);
    }
}
