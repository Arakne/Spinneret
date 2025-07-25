<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Tagged;

use Override;

class TaggedA implements MyTagInterface
{
    #[Override]
    public function f(): string
    {
        return 'aaa';
    }
}
