<?php

namespace Arakne\Tests\Spinneret\Database\Migration\Repository;

use Arakne\Spinneret\Database\Migration\MigrationInterface;
use Arakne\Spinneret\Database\Migration\Repository\NullMigrationRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NullMigrationRepositoryTest extends TestCase
{
    #[Test]
    public function test()
    {
        $repository = new NullMigrationRepository();

        $this->assertFalse($repository->isApplied($this->createMock(MigrationInterface::class)));
        $repository->markAsApplied($this->createMock(MigrationInterface::class));
        $repository->remove($this->createMock(MigrationInterface::class));
        $this->assertNull($repository->lastVersion());
        $repository->initialize();
    }
}
