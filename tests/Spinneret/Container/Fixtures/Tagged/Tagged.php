<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Tagged;

use Override;

class Tagged implements MyTagInterface
{
    public function __construct(
        public string $value,
    ) {}

    #[Override]
    public function f(): string
    {
        return $this->value;
    }
}
