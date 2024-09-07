<?php

namespace Arakne\Tests\Spinneret\Form\Csrf;

use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsrfTokenParametersTest extends TestCase
{
    #[Test]
    public function empty()
    {
        $parameters = new CsrfTokenParameters(null, null, null);

        $this->assertNull($parameters->token());
        $this->assertFalse($parameters->validate());
    }

    #[Test]
    public function missingKey()
    {
        $parameters = new CsrfTokenParameters(null, 'secret', 'input');

        $this->assertNull($parameters->token());
        $this->assertFalse($parameters->validate());
    }

    #[Test]
    public function missingSecret()
    {
        $parameters = new CsrfTokenParameters('key', null, 'input');

        $this->assertNull($parameters->token());
        $this->assertFalse($parameters->validate());
    }

    #[Test]
    public function missingInput()
    {
        $parameters = new CsrfTokenParameters('key', 'secret', null);

        $this->assertSame('96de09a0f8699191b28587118ac57df88bbf6c2d0c131d196dcd90f7efd68c93', $parameters->token());
        $this->assertFalse($parameters->validate());
    }

    #[Test]
    public function invalidInput()
    {
        $parameters = new CsrfTokenParameters('key', 'secret', 'invalid');

        $this->assertSame('96de09a0f8699191b28587118ac57df88bbf6c2d0c131d196dcd90f7efd68c93', $parameters->token());
        $this->assertFalse($parameters->validate());
    }

    #[Test]
    public function validInput()
    {
        $parameters = new CsrfTokenParameters('key', 'secret', '96de09a0f8699191b28587118ac57df88bbf6c2d0c131d196dcd90f7efd68c93');

        $this->assertSame('96de09a0f8699191b28587118ac57df88bbf6c2d0c131d196dcd90f7efd68c93', $parameters->token());
        $this->assertTrue($parameters->validate());
    }
}
