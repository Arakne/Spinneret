<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Field\RequestPath;
use Quatrevieux\Form\Transformer\Field\DefaultValue;
use Quatrevieux\Form\Validator\Constraint\Length;

class HelloRequestPath
{
    #[DefaultValue('world'), Length(min: 2, max: 15), RequestPath]
    public string $name;
}
