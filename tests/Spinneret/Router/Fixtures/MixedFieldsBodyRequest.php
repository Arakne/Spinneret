<?php

namespace Arakne\Tests\Spinneret\Router\Fixtures;

use Arakne\Spinneret\Router\Attribute\Get;
use Arakne\Spinneret\Router\Attribute\Post;
use Arakne\Spinneret\Router\Field\QueryString;
use Arakne\Spinneret\Router\Field\RequestAttribute;
use Arakne\Spinneret\Router\Field\RequestBody;
use Arakne\Spinneret\Router\Field\RequestHeader;
use Arakne\Spinneret\Router\Field\RequestPath;

#[RequestBody]
class MixedFieldsBodyRequest
{
    public function __construct(
        #[RequestPath]
        public int $id,
        #[QueryString]
        public ?string $name,
        #[RequestAttribute]
        public ?object $user,
        #[RequestHeader('Referer')]
        public ?string $referrer,
        public string $value,
    ) {}
}
