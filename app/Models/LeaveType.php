<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'leave_types';

    protected $fillable = [
        'name', 'code', 'category', 'color', 'is_paid',
        'requires_approval', 'max_days_per_year', 'allow_half_day',
        'requires_document', 'is_active', 'description',
    ];

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
