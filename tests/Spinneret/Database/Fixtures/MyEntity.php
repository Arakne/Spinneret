<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

final readonly class MyEntity
{
    public function __construct(
        public int $id,
        public string $name,
        public mixed $value,
    ) {
    }
}
