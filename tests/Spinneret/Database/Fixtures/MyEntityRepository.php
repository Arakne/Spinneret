<?php

namespace Arakne\Tests\Spinneret\Database\Fixtures;

use Arakne\Spinneret\Database\DatabaseConnectionInterface;

final readonly class MyEntityRepository
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
    ) {
    }

    public function init(): void
    {
        $this->connection->exec('CREATE TABLE IF NOT EXISTS my_entity (id INTEGER PRIMARY KEY, name TEXT, value TEXT)');
    }

    public function insert(MyEntity $entity): void
    {
        $this->connection
            ->prepare('INSERT INTO my_entity (id, name, value) VALUES (?, ?, ?)')
            ->pushInt($entity->id)
            ->pushString($entity->name)
            ->pushString(json_encode($entity->value))
            ->executeUpdate()
        ;
    }

    public function all(): array
    {
        return $this->connection
            ->query('SELECT * FROM my_entity')
            ->mapAssociativeArray(fn (array $row) => new MyEntity((int) $row['id'], $row['name'], json_decode($row['value'])))
        ;
    }
}
