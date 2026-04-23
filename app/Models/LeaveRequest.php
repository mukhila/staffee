<?php

namespace App\Models;

use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveType;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'type',
        'from_date', 'to_date', 'days', 'half_day', 'half_day_period',
        'reason', 'status', 'rejection_reason',
        'reviewed_by',
        'manager_approved_by', 'manager_approved_at',
        'hr_approved_by', 'hr_approved_at',
        'auto_approved', 'cancelled_at', 'cancelled_reason',
    ];

    protected $casts = [
        'from_date'           => 'date',
        'to_date'             => 'date',
        'half_day'            => 'boolean',
        'auto_approved'       => 'boolean',
        'manager_approved_at' => 'datetime',
        'hr_approved_at'      => 'datetime',
        'cancelled_at'        => 'datetime',
    ];

    const STATUS_COLORS = [
        'pending'          => 'warning',
        'manager_approved' => 'info',
        'approved'         => 'success',
        'auto_approved'    => 'success',
        'rejected'         => 'danger',
        'cancelled'        => 'secondary',
    ];

    const STATUS_LABELS = [
        'pending'          => 'Pending',
        'manager_approved' => 'Manager Approved',
        'approved'         => 'Approved',
        'auto_approved'    => 'Auto Approved',
        'rejected'         => 'Rejected',
        'cancelled'        => 'Cancelled',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function managerApprover()
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('level');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'auto_approved']);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('from_date', $year);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['rejected', 'cancelled']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return in_array($this->status, ['approved', 'auto_approved']); }
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'manager_approved'])
            && $this->from_date->isFuture();
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->leaveType?->name ?? ucfirst(str_replace('_', ' ', $this->type ?? ''));
    }
}
