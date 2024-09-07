<?php

namespace Arakne\Tests\Spinneret\Form\Csrf;

use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Tests\Spinneret\Form\Fixtures\BasicCsrfForm;
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
    public function setCsrf()
    {
        $r = new BasicCsrfForm();
        $psr = new ServerRequest('POST', 'http://localhost/csrf');
        $psr = $psr->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $this->assertSame($r, $this->helper->setCsrf($r, $psr));
        $this->assertInstanceOf(CsrfTokenParameters::class, $r->csrf);
        $this->assertSame('ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3', $r->csrf->token());

        $psr = $psr->withAttribute(ParsedCookie::class, '');
        $r = new BasicCsrfForm();
        $this->assertSame($r, $this->helper->setCsrf($r, $psr));
        $this->assertFalse(isset($r->csrf));
    }

    #[Test]
    public function form()
    {
        $req = new ServerRequest('POST', 'http://localhost/csrf');
        $req = $req->withAttribute(ParsedCookie::class, new ParsedCookie(
            token: 'a',
            creation: 0,
            expiration: 0,
            version: 1,
            data: null,
        ));

        $form = $this->helper->form(BasicCsrfForm::class, $req);
        $this->assertSame('ce6ce7356ed5476e1c0a87a0e838fd5c129a664afebf079f88e6056677d751b3', $form->view()['csrf']->value);
    }
}
