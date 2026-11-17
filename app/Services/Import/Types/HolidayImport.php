<?php

namespace App\Services\Import\Types;

use App\Models\Shift\ShiftHoliday;
use App\Services\Import\Concerns\ParsesValues;
use App\Services\Import\ImportType;

class HolidayImport implements ImportType
{
    use ParsesValues;

    private const TYPES = ['national', 'regional', 'company'];

    public function key(): string { return 'holidays'; }

    public function label(): string { return 'Holidays'; }

    public function description(): string
    {
        return "Load the year's public and company holidays. A holiday on the same date with the same name is updated, not duplicated.";
    }

    public function columns(): array
    {
        return [
            'date'      => ['required' => true,  'help' => 'YYYY-MM-DD',                         'example' => '2026-12-25'],
            'name'      => ['required' => true,  'help' => 'Holiday name',                       'example' => 'Christmas Day'],
            'type'      => ['required' => false, 'help' => 'national, regional or company; default national', 'example' => 'national'],
            'recurring' => ['required' => false, 'help' => 'yes if it falls on the same date every year; default no', 'example' => 'yes'],
        ];
    }

    public function validateRow(array $row, array &$seen): array
    {
        $date = $this->date($this->required($row, 'date'), 'date');
        $name = $this->required($row, 'name');
        $this->once($seen, $date . '|' . mb_strtolower($name), "Holiday \"{$name}\" on {$date}");

        return [
            'date'         => $date,
            'name'         => mb_substr($name, 0, 255),
            'holiday_type' => ($row['type'] ?? '') === '' ? 'national' : $this->oneOf($row['type'], self::TYPES, 'type'),
            'is_recurring' => $this->yesNo($row['recurring'] ?? '', 'recurring', false),
            'is_active'    => true,
        ];
    }

    public function import(array $clean, int $actorId): string
    {
        $existing = ShiftHoliday::whereDate('date', $clean['date'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($clean['name'])])
            ->first();

        if ($existing) {
            $existing->update($clean);

            return 'updated';
        }

        ShiftHoliday::create($clean);

        return 'created';
    }
}
