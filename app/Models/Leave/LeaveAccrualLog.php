<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeaveAccrualLog extends Model
{
    protected $fillable = [
        'user_id', 'leave_type_id', 'leave_balance_id',
        'period_start', 'period_end', 'days_accrued', 'accrual_method', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveBalance()
    {
        return $this->belongsTo(LeaveBalance::class);
    }
}
