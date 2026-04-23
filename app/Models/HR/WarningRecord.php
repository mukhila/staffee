<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WarningRecord extends Model
{
    protected $table = 'warning_records';

    protected $fillable = [
        'user_id', 'issued_by', 'warning_type', 'reason', 'incident_date',
        'action_required', 'response_deadline',
        'employee_response', 'employee_responded_at',
        'is_acknowledged', 'acknowledged_at',
        'follow_up_date', 'follow_up_notes', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'incident_date'           => 'date',
            'response_deadline'       => 'date',
            'follow_up_date'          => 'date',
            'is_acknowledged'         => 'boolean',
            'acknowledged_at'         => 'datetime',
            'employee_responded_at'   => 'datetime',
            'resolved_at'             => 'datetime',
        ];
    }

    public function employee()  { return $this->belongsTo(User::class, 'user_id'); }
    public function issuedBy()  { return $this->belongsTo(User::class, 'issued_by'); }
    public function resolver()  { return $this->belongsTo(User::class, 'resolved_by'); }

    public function acknowledge(): void
    {
        $this->update(['is_acknowledged' => true, 'acknowledged_at' => now()]);
    }

    public function isResolved(): bool { return (bool) $this->resolved_at; }
    public function isOverdue(): bool
    {
        return $this->response_deadline && $this->response_deadline->isPast() && !$this->employee_responded_at;
    }
}
