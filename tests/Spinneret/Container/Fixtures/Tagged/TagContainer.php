<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Tagged;

use function iterator_to_array;

class TagContainer
{
    public array $tagged;

    /**
     * @param iterable<MyTagInterface> $tagged
     */
    public function __construct(iterable $tagged)
    {
        $this->tagged = iterator_to_array($tagged);
    }
}
