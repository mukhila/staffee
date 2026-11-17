<?php

namespace App\Services\Import\Types;

use App\Models\Department;
use App\Services\Import\Concerns\ParsesValues;
use App\Services\Import\ImportType;

class DepartmentImport implements ImportType
{
    use ParsesValues;

    public function key(): string { return 'departments'; }

    public function label(): string { return 'Departments'; }

    public function description(): string
    {
        return 'Add departments, or update the description and status of ones that already exist (matched by name).';
    }

    public function columns(): array
    {
        return [
            'name'        => ['required' => true,  'help' => 'Department name', 'example' => 'Engineering'],
            'description' => ['required' => false, 'help' => 'Optional',        'example' => 'Product and platform teams'],
            'active'      => ['required' => false, 'help' => 'yes or no; default yes', 'example' => 'yes'],
        ];
    }

    public function validateRow(array $row, array &$seen): array
    {
        $name = $this->required($row, 'name');
        if (mb_strlen($name) > 255) {
            throw new \App\Services\Import\RowError('"name" is longer than 255 characters.');
        }
        $this->once($seen, mb_strtolower($name), "Department \"{$name}\"");

        return [
            'name'        => $name,
            'description' => ($row['description'] ?? '') !== '' ? $row['description'] : null,
            'is_active'   => $this->yesNo($row['active'] ?? '', 'active', true),
        ];
    }

    public function import(array $clean, int $actorId): string
    {
        $department = Department::whereRaw('LOWER(name) = ?', [mb_strtolower($clean['name'])])->first();

        if ($department) {
            $department->update(['description' => $clean['description'] ?? $department->description, 'is_active' => $clean['is_active']]);

            return 'updated';
        }

        Department::create($clean);

        return 'created';
    }
}
