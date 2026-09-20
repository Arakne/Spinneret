<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Attribute\Get;
use Arakne\Spinneret\Router\Field\RequestAttribute;
use Arakne\Spinneret\Router\Field\RequestHeader;
use Arakne\Spinneret\Router\Field\RequestPath;

#[Get('/foo-{id}')]
class MixedFieldsGetRequest
{
    public function __construct(
        #[RequestPath]
        public int $id,
        public ?string $name,
        #[RequestAttribute]
        public ?object $user,
        #[RequestHeader('Referer')]
        public ?string $referrer,
    ) {}
}
