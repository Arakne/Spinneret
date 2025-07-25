<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Tagged;

use Override;

class TaggedB implements MyTagInterface
{
    #[Override]
    public function f(): string
    {
        return 'b';
    }
}
