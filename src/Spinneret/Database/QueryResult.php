<?php

namespace Arakne\Spinneret\Database;

use PDO;
use PDOStatement;

final readonly class QueryResult
{
    public function __construct(
        private PDOStatement $statement
    ) {
    }

    public function asAssociativeArray(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asColumns(int $colum): array
    {
        return $this->statement->fetchAll(PDO::FETCH_COLUMN, $colum);
    }

    /**
     * @param callable(array<string, mixed>):R $transformer
     * @return list<R>
     * @template R
     */
    public function mapAssociativeArray(callable $transformer): array
    {
        $ret = [];

        while ($row = $this->statement->fetch(PDO::FETCH_ASSOC)) {
            $ret[] = $transformer($row);
        }

        return $ret;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function fetchAssociativeArray(): array|false
    {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchColumn(int $colum): mixed
    {
        return $this->statement->fetchColumn($colum);
    }
}
