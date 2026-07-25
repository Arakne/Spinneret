<?php

namespace Arakne\Spinneret\Database;

/**
 * Represents the result of a query
 */
interface QueryResultInterface
{
    /**
     * Fetch all rows as associative arrays
     * Each element of the returned array will represent a row as an associative array with column names as keys.
     *
     * @return list<array<string, mixed>>
     */
    public function asAssociativeArray(): array;

    /**
     * Fetch a single column from all rows
     *
     * @param int $colum The column index to fetch. Starts at 0.
     *
     * @return list<mixed>
     */
    public function asColumns(int $colum): array;

    /**
     * Fetch all rows as associative arrays and apply a transformation to each row
     *
     * The transformation function will receive each row as an associative array and must return a new value.
     * This method will return a list of transformed values.
     *
     * @param callable(array<string, mixed>):R $transformer The transformation function. Takes as parameter the row and returns the transformed value.
     *
     * @return list<R>
     * @template R
     */
    public function mapAssociativeArray(callable $transformer): array;

    /**
     * Fetch a single row as an associative array, and move the internal pointer to the next row
     *
     * The returned array will represent a row as an associative array with column names as keys.
     * If there are no more rows, this method will return false.
     *
     * @return array<string, mixed>|false
     */
    public function fetchAssociativeArray(): array|false;

    /**
     * Fetch a single column from the current row
     * If there are no more rows, this method will return false.
     *
     * @param int $colum The column index to fetch. Starts at 0.
     * @return mixed
     */
    public function fetchColumn(int $colum): mixed;
}
