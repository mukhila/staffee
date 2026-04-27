<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'user_id', 'leave_type_id', 'year',
        'opening_balance', 'carry_forward_days', 'accrued_days',
        'used_days', 'pending_days', 'available_balance', 'last_accrual_date',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
