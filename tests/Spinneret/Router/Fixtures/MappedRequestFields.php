<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Field\QueryString;
use Arakne\Spinneret\Router\Field\RequestBody;

class MappedRequestFields
{
    public function __construct(
        #[QueryString('search')]
        public string $query,
        #[RequestBody('content')]
        public string $body,
    ) {}
}
