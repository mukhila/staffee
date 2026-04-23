<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResignationRequest extends Model
{
    protected $table = 'resignation_requests';

    protected $fillable = [
        'user_id', 'submitted_date', 'requested_last_date', 'official_last_date',
        'notice_period_days', 'resignation_type', 'reason',
        'notice_waived', 'waiver_reason', 'status',
        'manager_id', 'manager_reviewed_at', 'manager_decision', 'manager_notes',
        'hr_reviewed_by', 'hr_reviewed_at', 'hr_notes',
        'withdrawal_reason', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_date'      => 'date',
            'requested_last_date' => 'date',
            'official_last_date'  => 'date',
            'notice_waived'       => 'boolean',
            'manager_reviewed_at' => 'datetime',
            'hr_reviewed_at'      => 'datetime',
            'withdrawn_at'        => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hrReviewer()
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by');
    }

    public function termination()
    {
        return $this->hasOne(TerminationRequest::class, 'resignation_id');
    }

    public function daysRemainingInNotice(): ?int
    {
        if (!$this->official_last_date) {
            return null;
        }
        $remaining = now()->startOfDay()->diffInDays($this->official_last_date->startOfDay(), false);
        return max(0, $remaining);
    }

    public function isWithdrawable(): bool
    {
        return in_array($this->status, ['pending', 'manager_reviewing', 'manager_accepted']);
    }
}
