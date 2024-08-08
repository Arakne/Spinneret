<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Field\QueryString;
use Arakne\Spinneret\Router\Field\RequestBody;
use Quatrevieux\Form\Validator\Constraint\Length;

#[RequestBody]
class MixedFieldsRequest
{
    #[QueryString, Length(min: 10, max: 10)]
    public string $key;
    public string $login;
    public string $password;
}
