<?php

namespace App\Models\Asset;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id', 'user_id', 'assigned_by', 'assigned_at',
        'returned_at', 'return_condition', 'return_notes', 'is_current',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at'  => 'datetime',
            'returned_at'  => 'datetime',
            'is_current'   => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
