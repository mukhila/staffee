<?php

namespace App\Models\Leave;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CompOffCredit extends Model
{
    protected $fillable = [
        'user_id', 'worked_date', 'days', 'reason',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'worked_date' => 'date',
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
