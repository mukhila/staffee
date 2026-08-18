<?php

namespace App\Models\Asset;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Asset extends Model
{
    protected $fillable = [
        'asset_tag', 'name', 'category', 'brand', 'model_number',
        'serial_number', 'purchase_date', 'purchase_cost', 'warranty_expiry',
        'status', 'location', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_cost'   => 'decimal:6',
            'purchase_date'   => 'date',
            'warranty_expiry' => 'date',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->where('is_current', true);
    }

    public function currentUser()
    {
        return $this->hasOneThrough(
            User::class,
            AssetAssignment::class,
            'asset_id',   // FK on asset_assignments
            'id',         // FK on users
            'id',         // local key on assets
            'user_id'     // local key on asset_assignments
        )->where('asset_assignments.is_current', true);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', 'assigned');
    }
}
