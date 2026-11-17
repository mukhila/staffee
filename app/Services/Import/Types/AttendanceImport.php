<?php

namespace App\Services\Import\Types;

use App\Models\Attendance;
use App\Services\Import\Concerns\ParsesValues;
use App\Services\Import\ImportType;
use App\Services\Import\RowError;
use Carbon\Carbon;

class AttendanceImport implements ImportType
{
    use ParsesValues;

    private const STATUSES = ['present', 'absent', 'leave', 'halfday', 'holiday'];

    public function key(): string { return 'attendance'; }

    public function label(): string { return 'Attendance (past days)'; }

    public function description(): string
    {
        return 'Load past attendance, for example from a biometric device export. A record for the same person and date is replaced.';
    }

    public function columns(): array
    {
        return [
            'email'     => ['required' => true,  'help' => 'Staff email',                                   'example' => 'priya@example.com'],
            'date'      => ['required' => true,  'help' => 'YYYY-MM-DD; today or earlier',                  'example' => now()->subDay()->toDateString()],
            'check_in'  => ['required' => false, 'help' => '24-hour time, e.g. 09:05',                      'example' => '09:05'],
            'check_out' => ['required' => false, 'help' => '24-hour time, after check_in',                  'example' => '18:10'],
            'status'    => ['required' => false, 'help' => 'present, absent, leave, halfday or holiday; default present if there is a check-in, otherwise absent', 'example' => 'present'],
        ];
    }

    public function validateRow(array $row, array &$seen): array
    {
        $user = $this->userByEmail($this->required($row, 'email'));
        $date = $this->date($this->required($row, 'date'), 'date');

        if (Carbon::parse($date)->isFuture()) {
            throw new RowError("\"date\" {$date} is in the future.");
        }

        $in  = ($row['check_in'] ?? '') !== '' ? $this->time($row['check_in'], 'check_in') : null;
        $out = ($row['check_out'] ?? '') !== '' ? $this->time($row['check_out'], 'check_out') : null;

        if ($out && !$in) {
            throw new RowError('There is a check_out but no check_in.');
        }
        if ($in && $out && $out <= $in) {
            throw new RowError('"check_out" must be after "check_in".');
        }

        $status = ($row['status'] ?? '') !== ''
            ? $this->oneOf($row['status'], self::STATUSES, 'status')
            : ($in ? 'present' : 'absent');

        $this->once($seen, "{$user->id}|{$date}", "{$user->email} on {$date}");

        return [
            'user_id'        => $user->id,
            'date'           => $date,
            'check_in'       => $in,
            'check_out'      => $out,
            'status'         => $status,
            'worked_minutes' => $in && $out ? (int) Carbon::parse($in)->diffInMinutes(Carbon::parse($out)) : null,
        ];
    }

    public function import(array $clean, int $actorId): string
    {
        $existing = Attendance::where('user_id', $clean['user_id'])->whereDate('date', $clean['date'])->first();

        if ($existing) {
            $existing->update($clean);

            return 'updated';
        }

        Attendance::create($clean);

        return 'created';
    }
}
