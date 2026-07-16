<?php

namespace Arakne\Spinneret\Database;

use Override;
use PDO;
use PDOStatement;

/**
 * Implementation of QueryResultInterface using PDOStatement.
 */
final readonly class QueryResult implements QueryResultInterface
{
    public function __construct(
        private PDOStatement $statement
    ) {}

    #[Override]
    public function asAssociativeArray(): array
    {
        /** @var list<array<string, mixed>> */
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    #[Override]
    public function asColumns(int $colum): array
    {
        /** @var list<mixed> */
        return $this->statement->fetchAll(PDO::FETCH_COLUMN, $colum);
    }

    #[Override]
    public function mapAssociativeArray(callable $transformer): array
    {
        $ret = [];

        /** @psalm-suppress MixedAssignment */
        while (($row = $this->statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            /** @var array<string, mixed> $row */
            $ret[] = $transformer($row);
        }

        return $ret;
    }

    #[Override]
    public function fetchAssociativeArray(): array|false
    {
        /** @var array<string, mixed>|false */
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    #[Override]
    public function fetchColumn(int $colum): mixed
    {
        return $this->statement->fetchColumn($colum);
    }
}
