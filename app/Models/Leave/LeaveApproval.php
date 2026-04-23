<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_request_id', 'approver_id', 'level', 'action', 'notes', 'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    const LEVEL_LABELS = [1 => 'Manager', 2 => 'HR', 3 => 'Finance'];

    const ACTION_COLORS = [
        'approved'  => 'success',
        'rejected'  => 'danger',
        'forwarded' => 'info',
        'cancelled' => 'secondary',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(\App\Models\LeaveRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVEL_LABELS[$this->level] ?? "Level {$this->level}";
    }

    public function getActionColorAttribute(): string
    {
        return self::ACTION_COLORS[$this->action] ?? 'secondary';
    }
}
