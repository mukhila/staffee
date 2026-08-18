<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'custom_type',
        'purpose',
        'status',
        'admin_notes',
        'document_path',
        'requested_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    const TYPE_LABELS = [
        'experience_letter'  => 'Experience Letter',
        'salary_certificate' => 'Salary Certificate',
        'noc'                => 'No Objection Certificate (NOC)',
        'appointment_letter' => 'Appointment Letter',
        'promotion_letter'   => 'Promotion Letter',
        'custom'             => 'Custom Document',
    ];

    const STATUS_COLORS = [
        'pending'    => 'warning',
        'processing' => 'info',
        'ready'      => 'success',
        'rejected'   => 'danger',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        if ($this->document_type === 'custom' && $this->custom_type) {
            return $this->custom_type;
        }

        return self::TYPE_LABELS[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
