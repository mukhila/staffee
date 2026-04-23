<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TerminationRequest extends Model
{
    protected $table = 'termination_requests';

    protected $fillable = [
        'user_id', 'initiated_by', 'termination_type', 'reason',
        'last_working_date', 'resignation_id', 'status', 'settlement_status',
        'approved_by', 'approved_at', 'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'last_working_date' => 'date',
            'approved_at'       => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function resignation()
    {
        return $this->belongsTo(ResignationRequest::class, 'resignation_id');
    }

    public function exitChecklist()
    {
        return $this->hasOne(ExitChecklist::class, 'termination_id');
    }

    public function settlement()
    {
        return $this->hasOne(FinalSettlement::class, 'termination_id');
    }

    public function isSettlementReady(): bool
    {
        $checklist = $this->exitChecklist;
        return $checklist && $checklist->is_complete;
    }
}
