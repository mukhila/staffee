<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReportSubscription extends Model
{
    public const FREQUENCIES = [
        'weekly'  => 'Every Monday, covering last week',
        'monthly' => 'On the 1st, covering last month',
    ];

    protected $fillable = ['user_id', 'report', 'frequency', 'department_id', 'is_active', 'last_sent_at', 'last_error'];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** The period this subscription reports on. */
    public function period(): string
    {
        return $this->frequency === 'monthly' ? 'last-month' : 'last-week';
    }

    /** Due today, and not already sent today. */
    public function isDue(Carbon $today): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $dayMatches = $this->frequency === 'monthly' ? $today->day === 1 : $today->isMonday();

        return $dayMatches && (!$this->last_sent_at || $this->last_sent_at->lt($today->copy()->startOfDay()));
    }

    public function nextSendDate(?Carbon $from = null): Carbon
    {
        $from = ($from ?? Carbon::today())->copy()->startOfDay();

        if ($this->frequency === 'monthly') {
            return $from->day === 1 && !$this->sentOn($from) ? $from : $from->copy()->addMonthNoOverflow()->startOfMonth();
        }

        return $from->isMonday() && !$this->sentOn($from) ? $from : $from->copy()->next(Carbon::MONDAY);
    }

    private function sentOn(Carbon $day): bool
    {
        return $this->last_sent_at && $this->last_sent_at->isSameDay($day);
    }
}
