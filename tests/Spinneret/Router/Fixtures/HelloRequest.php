<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Quatrevieux\Form\Transformer\Field\DefaultValue;
use Quatrevieux\Form\Validator\Constraint\Length;

class HelloRequest
{
    #[DefaultValue('world'), Length(min: 2, max: 15)]
    public string $name;
}
