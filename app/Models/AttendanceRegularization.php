<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRegularization extends Model
{
    use \App\Traits\Auditable;
    protected $fillable = [
        'user_id', 'date', 'requested_check_in', 'requested_check_out',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'date'        => 'date',
        'reviewed_at' => 'datetime',
    ];

    const STATUS_COLORS = [
        'pending'  => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
