<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'year',
        'opening_balance', 'carry_forward_days', 'accrued_days',
        'used_days', 'pending_days', 'last_accrual_date',
    ];

    protected $casts = [
        'last_accrual_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function accrualLogs()
    {
        return $this->hasMany(LeaveAccrualLog::class);
    }

    /**
     * Effective available days (excludes pending requests).
     * available_balance is a stored computed column: opening + carry_forward + accrued - used.
     * effective_available subtracts pending requests too.
     */
    public function getEffectiveAvailableAttribute(): float
    {
        return max(0, $this->available_balance - $this->pending_days);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForUser($query, \App\Models\User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForType($query, LeaveType $type)
    {
        return $query->where('leave_type_id', $type->id);
    }

    public static function getOrCreate(\App\Models\User $user, LeaveType $type, int $year): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
            ['opening_balance' => 0, 'accrued_days' => 0, 'used_days' => 0, 'pending_days' => 0]
        );
    }
}
