<?php

namespace App\Models\Shift;

use Illuminate\Database\Eloquent\Model;

class AttendanceException extends Model
{
    protected $fillable = [
        'attendance_id', 'user_id', 'shift_id', 'date', 'exception_type',
        'expected_start', 'expected_end', 'actual_start', 'actual_end',
        'deviation_minutes', 'overtime_minutes', 'reason', 'status',
        'reviewed_by', 'reviewed_at', 'manager_notes', 'is_paid_overtime',
    ];

    protected $casts = [
        'date'             => 'date',
        'expected_start'   => 'datetime',
        'expected_end'     => 'datetime',
        'actual_start'     => 'datetime',
        'actual_end'       => 'datetime',
        'reviewed_at'      => 'datetime',
        'is_paid_overtime' => 'boolean',
    ];

    const TYPES = [
        'late_arrival'     => 'Late Arrival',
        'early_departure'  => 'Early Departure',
        'absent'           => 'Absent',
        'overtime'         => 'Overtime',
        'half_day'         => 'Half Day',
        'no_check_out'     => 'No Check-Out',
        'unscheduled_work' => 'Unscheduled Work',
    ];

    const TYPE_COLORS = [
        'late_arrival'     => 'warning',
        'early_departure'  => 'warning',
        'absent'           => 'danger',
        'overtime'         => 'info',
        'half_day'         => 'secondary',
        'no_check_out'     => 'danger',
        'unscheduled_work' => 'secondary',
    ];

    public function attendance()
    {
        return $this->belongsTo(\App\Models\Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(\App\Models\User $approver, ?string $notes = null): void
    {
        $this->update([
            'status'        => 'approved',
            'reviewed_by'   => $approver->id,
            'reviewed_at'   => now(),
            'manager_notes' => $notes,
        ]);
    }

    public function reject(\App\Models\User $approver, ?string $notes = null): void
    {
        $this->update([
            'status'        => 'rejected',
            'reviewed_by'   => $approver->id,
            'reviewed_at'   => now(),
            'manager_notes' => $notes,
        ]);
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->exception_type] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->exception_type] ?? ucwords(str_replace('_', ' ', $this->exception_type));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'      => 'warning',
            'approved'     => 'success',
            'rejected'     => 'danger',
            'auto_approved'=> 'info',
            default        => 'secondary',
        };
    }
}
