<?php

namespace Arakne\Tests\Spinneret\Fixtures\Hello;

use Quatrevieux\Form\Transformer\Field\Trim;
use Quatrevieux\Form\Validator\Constraint\Length;
use Quatrevieux\Form\Validator\Constraint\Regex;

final class HelloRequest
{
    #[Length(min: 2, max: 25), Regex('^[a-z -]+$', flags: 'i'), Trim]
    public ?string $name;
}
