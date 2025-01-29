<?php

namespace Arakne\Spinneret\Database\Migration;

final readonly class MigrationStatus
{
    public function __construct(
        public MigrationInterface $migration,
        public bool $applied,
    ) {}
}
