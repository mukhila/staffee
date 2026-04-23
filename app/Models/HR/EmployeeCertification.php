<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeCertification extends Model
{
    protected $table = 'employee_certifications';

    protected $fillable = [
        'user_id', 'name', 'issuing_organization', 'issue_date',
        'expiry_date', 'credential_id', 'credential_url', 'file_path',
    ];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    public function employee() { return $this->belongsTo(User::class, 'user_id'); }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 60): bool
    {
        return $this->expiry_date && $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    public function scopeActive($query)
    {
        return $query->where(fn ($q) =>
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now())
        );
    }
}
