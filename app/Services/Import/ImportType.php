<?php

namespace App\Services\Import;

/**
 * One kind of record that can be imported from a CSV file.
 */
interface ImportType
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /**
     * Expected columns, in template order.
     *
     * @return array<string, array{required: bool, help: string, example: string}>
     */
    public function columns(): array;

    /**
     * Check one row. Return the cleaned values to import, or throw
     * RowError with a message the admin can act on.
     *
     * @param  array<string, string>  $row  trimmed values keyed by column name
     * @param  array<string, true>  $seen  keys already used by earlier rows in this file
     *
     * @throws RowError
     */
    public function validateRow(array $row, array &$seen): array;

    /** Write one cleaned row. Returns 'created' or 'updated'. */
    public function import(array $clean, int $actorId): string;
}
