<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Attribute\Get;
use Quatrevieux\Form\Transformer\Field\HttpField;

#[Get('/search')]
class MappedQueryStringRequest
{
    public function __construct(
        #[HttpField('search')]
        public string $query,
    ) {}
}
