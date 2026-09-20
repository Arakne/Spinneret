<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Field\RequestPath;

class MappedRequestPath
{
    public function __construct(
        #[RequestPath('slug')]
        public string $id,
    ) {}
}
