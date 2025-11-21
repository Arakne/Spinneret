<?php

namespace Arakne\Tests\Spinneret\Form\Fixtures;

use Arakne\Spinneret\Form\Csrf\Csrf;
use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;

class SimpleFormWithCsrf
{
    public string $foo;
    public int $bar;

    #[Csrf(self::class)]
    public CsrfTokenParameters $csrf;
}
