<?php

namespace App\Models\Shift;

use Illuminate\Database\Eloquent\Model;

class ShiftChangeRequest extends Model
{
    protected $fillable = [
        'requester_id', 'current_shift_id', 'requested_shift_id',
        'swap_with_user_id', 'effective_date', 'reason', 'status',
        'reviewed_by', 'reviewed_at', 'manager_notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'reviewed_at'    => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }

    public function currentShift()
    {
        return $this->belongsTo(Shift::class, 'current_shift_id');
    }

    public function requestedShift()
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function swapWithUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'swap_with_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }
}
