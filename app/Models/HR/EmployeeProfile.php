<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id', 'date_of_birth', 'gender', 'blood_group', 'marital_status',
        'nationality', 'national_id', 'national_id_type',
        'perm_address_line1', 'perm_address_line2', 'perm_city', 'perm_state',
        'perm_postal_code', 'perm_country',
        'joining_date', 'probation_end_date', 'contract_type', 'contract_end_date',
        'notice_period_days', 'work_location', 'current_salary', 'salary_currency',
        'linkedin_url', 'github_url', 'bio',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'      => 'date',
            'joining_date'       => 'date',
            'probation_end_date' => 'date',
            'contract_end_date'  => 'date',
            'current_salary'     => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Satellite relationships (all go through user_id on their own tables) ──

    public function education(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class, 'user_id', 'user_id')
                    ->orderByDesc('start_year');
    }

    public function experience(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class, 'user_id', 'user_id')
                    ->orderByDesc('start_date');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'user_id', 'user_id')
                    ->orderBy('category')->orderBy('name');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class, 'user_id', 'user_id')
                    ->orderByDesc('issue_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'user_id', 'user_id')
                    ->orderByDesc('created_at');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class, 'user_id', 'user_id')
                    ->orderByDesc('is_primary');
    }

    // ── Computed helpers ──────────────────────────────────────────────────────

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getYearsOfServiceAttribute(): ?float
    {
        return $this->joining_date
            ? round($this->joining_date->floatDiffInYears(now()), 1)
            : null;
    }

    public function isOnProbation(): bool
    {
        return $this->probation_end_date && $this->probation_end_date->isFuture();
    }

    public function isContractExpiring(int $withinDays = 30): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }
        return $this->contract_end_date->isBetween(now(), now()->addDays($withinDays));
    }
}
