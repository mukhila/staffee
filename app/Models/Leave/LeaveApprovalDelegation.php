<?php

namespace App\Models\Leave;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LeaveApprovalDelegation extends Model
{
    protected $fillable = ['delegator_id', 'delegate_id', 'from_date', 'to_date', 'reason'];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function delegator()
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    /** Delegations in force on the given date (default: today). */
    public function scopeActiveOn($query, ?Carbon $date = null)
    {
        $day = ($date ?? Carbon::today())->toDateString();

        return $query->whereDate('from_date', '<=', $day)->whereDate('to_date', '>=', $day);
    }

    public function isActive(): bool
    {
        return Carbon::today()->betweenIncluded($this->from_date, $this->to_date);
    }
}
