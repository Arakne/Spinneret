<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;

final readonly class OtherRepository
{
    public function __construct(
        public DatabaseConnectionInterface $test,
        public DatabaseConnectionInterface $other,
    ) {
    }
}
