<?php

namespace App\Services\Import\Types;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Services\Import\Concerns\ParsesValues;
use App\Services\Import\ImportType;
use App\Services\Import\RowError;

class LeaveBalanceImport implements ImportType
{
    use ParsesValues;

    private ?array $types = null;

    public function key(): string { return 'leave-balances'; }

    public function label(): string { return 'Leave opening balances'; }

    public function description(): string
    {
        return "Set each person's opening leave balance for a year, for example when moving from another system. Days already used, accrued or pending are not changed.";
    }

    public function columns(): array
    {
        return [
            'email'           => ['required' => true, 'help' => 'Staff email',                         'example' => 'priya@example.com'],
            'leave_type'      => ['required' => true, 'help' => 'Leave type code, e.g. CL or SL',      'example' => 'CL'],
            'year'            => ['required' => true, 'help' => 'Year, e.g. 2026',                     'example' => (string) now()->year],
            'opening_balance' => ['required' => true, 'help' => 'Days, in steps of 0.5',               'example' => '7.5'],
        ];
    }

    public function validateRow(array $row, array &$seen): array
    {
        $user = $this->userByEmail($this->required($row, 'email'));

        $code = strtoupper($this->required($row, 'leave_type'));
        $typeId = $this->types()[$code]
            ?? throw new RowError("Leave type \"{$code}\" does not exist. Use one of: " . implode(', ', array_keys($this->types())) . '.');

        $year = $this->required($row, 'year');
        if (!preg_match('/^\d{4}$/', $year) || (int) $year < 2000 || (int) $year > now()->year + 1) {
            throw new RowError("\"year\" must be a year between 2000 and " . (now()->year + 1) . " (got \"{$year}\").");
        }

        $days = $this->required($row, 'opening_balance');
        if (!is_numeric($days) || (float) $days < 0 || (float) $days > 365 || fmod((float) $days * 2, 1.0) !== 0.0) {
            throw new RowError("\"opening_balance\" must be a number of days from 0 to 365 in steps of 0.5 (got \"{$days}\").");
        }

        $this->once($seen, "{$user->id}|{$typeId}|{$year}", "{$user->email} / {$code} / {$year}");

        return [
            'user_id'         => $user->id,
            'leave_type_id'   => $typeId,
            'year'            => (int) $year,
            'opening_balance' => number_format((float) $days, 1, '.', ''),
        ];
    }

    public function import(array $clean, int $actorId): string
    {
        $balance = LeaveBalance::where('user_id', $clean['user_id'])
            ->where('leave_type_id', $clean['leave_type_id'])
            ->where('year', $clean['year'])
            ->first();

        if ($balance) {
            $balance->update(['opening_balance' => $clean['opening_balance']]);

            return 'updated';
        }

        LeaveBalance::create($clean + ['accrued_days' => 0, 'used_days' => 0, 'pending_days' => 0, 'carry_forward_days' => 0]);

        return 'created';
    }

    private function types(): array
    {
        return $this->types ??= LeaveType::pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtoupper($code) => $id])
            ->all();
    }
}
