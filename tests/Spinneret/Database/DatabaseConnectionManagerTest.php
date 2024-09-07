<?php

namespace Arakne\Tests\Spinneret\Database;

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;
use Arakne\Spinneret\Database\DatabaseConnection;
use Arakne\Spinneret\Database\DatabaseConnectionManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionManagerTest extends TestCase
{
    private DatabaseConnectionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new DatabaseConnectionManager(
            new DatabaseConfig(
                new ConnectionConfig(TestConnectionEnum::Test, 'sqlite::memory:'),
                new ConnectionConfig(TestConnectionEnum::Other, 'sqlite::memory:'),
            )
        );
    }

    protected function tearDown(): void
    {
        unset($this->manager);
    }

    #[Test]
    public function getStringParameter()
    {
        $connection = $this->manager->get('Test');
        $this->assertInstanceOf(DatabaseConnection::class, $connection);
        $this->assertSame($connection, $this->manager->get('Test'));

        $connection = $this->manager->get('Other');
        $this->assertInstanceOf(DatabaseConnection::class, $connection);
        $this->assertSame($connection, $this->manager->get('Other'));
        $this->assertNotSame($this->manager->get('Test'), $this->manager->get('Other'));
    }

    #[Test]
    public function getEnumParameter()
    {
        $connection = $this->manager->get(TestConnectionEnum::Test);
        $this->assertInstanceOf(DatabaseConnection::class, $connection);
        $this->assertSame($connection, $this->manager->get(TestConnectionEnum::Test));

        $connection = $this->manager->get(TestConnectionEnum::Other);
        $this->assertInstanceOf(DatabaseConnection::class, $connection);
        $this->assertSame($connection, $this->manager->get(TestConnectionEnum::Other));
        $this->assertNotSame($this->manager->get(TestConnectionEnum::Test), $this->manager->get(TestConnectionEnum::Other));
    }
}

enum TestConnectionEnum
{
    case Test;
    case Other;
}
