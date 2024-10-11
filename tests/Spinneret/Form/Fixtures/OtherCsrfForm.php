<?php

namespace Arakne\Tests\Spinneret\Form\Fixtures;

use Arakne\Spinneret\Form\Csrf\Csrf;
use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;

class OtherCsrfForm
{
    #[Csrf('lol')]
    public CsrfTokenParameters $csrf;

    #[Csrf(self::class)]
    public CsrfTokenParameters $other;
}
