<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    protected $table = 'employee_documents';

    protected $fillable = [
        'user_id', 'document_type', 'name', 'file_path',
        'file_size', 'mime_type', 'uploaded_by',
        'is_verified', 'verified_by', 'verified_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function employee()   { return $this->belongsTo(User::class, 'user_id'); }
    public function uploader()   { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function verifier()   { return $this->belongsTo(User::class, 'verified_by'); }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)        return "{$bytes} B";
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function verify(User $verifier): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'resume'            => 'Resume / CV',
            'id_proof'          => 'ID Proof',
            'offer_letter'      => 'Offer Letter',
            'contract'          => 'Employment Contract',
            'increment_letter'  => 'Increment Letter',
            'relieving_letter'  => 'Relieving Letter',
            'experience_letter' => 'Experience Letter',
            'nda'               => 'NDA / Non-Compete',
            'appraisal'         => 'Appraisal Letter',
            default             => 'Other Document',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
