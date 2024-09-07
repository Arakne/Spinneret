<?php

namespace Arakne\Tests\Spinneret\Bus\Fixtures;

use phpDocumentor\Reflection\Types\Object_;

class GenericHandler
{
    public function __invoke(object $command): string
    {
        return md5(serialize($command));
    }
}
