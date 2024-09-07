<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\ShowUser;

use Arakne\Spinneret\Security\Serializer\ParsedCookie;

readonly class NotLoggedResponse
{
    public function __construct(
        public ParsedCookie $cookie,
    ) {
    }
}
