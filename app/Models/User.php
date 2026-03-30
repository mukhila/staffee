<?php

namespace App\Models;

use App\Models\HR\EmployeeProfile;
use App\Models\HR\LifecycleEvent;
use App\Models\HR\PromotionRequest;
use App\Models\HR\ResignationRequest;
use App\Models\HR\SalaryRevision;
use App\Models\HR\TerminationRequest;
use App\Models\HR\WarningRecord;
use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'address',
        'department_id', 'reporting_to', 'is_active', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'reporting_to');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'assigned_to');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->appNotifications()->whereNull('read_at')->count();
    }

    // ─── HR relationships ─────────────────────────────────────────────────────

    public function profile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function lifecycleEvents()
    {
        return $this->hasMany(LifecycleEvent::class)->orderByDesc('effective_date');
    }

    public function salaryRevisions()
    {
        return $this->hasMany(SalaryRevision::class)->orderByDesc('effective_date');
    }

    public function promotions()
    {
        return $this->hasMany(PromotionRequest::class);
    }

    public function resignations()
    {
        return $this->hasMany(ResignationRequest::class);
    }

    public function terminations()
    {
        return $this->hasMany(TerminationRequest::class);
    }

    public function warnings()
    {
        return $this->hasMany(WarningRecord::class);
    }

    // ── Convenience ──────────────────────────────────────────────────────────

    public function currentSalary(): ?float
    {
        return (float) ($this->profile?->current_salary
            ?? $this->salaryRevisions()->latest('effective_date')->value('new_salary'));
    }

    public function latestResignation(): ?ResignationRequest
    {
        return $this->resignations()->latest()->first();
    }

    public function isTerminated(): bool
    {
        return $this->employment_status === 'terminated';
    }

    public function isOnNoticePeriod(): bool
    {
        return $this->employment_status === 'notice_period';
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->whereNotIn('employment_status', ['terminated', 'resigned']);
    }

    public function scopeActiveInDepartment($query, int $departmentId)
    {
        return $query->active()->where('department_id', $departmentId);
    }

    public function scopeOnNoticePeriod($query)
    {
        return $query->where('employment_status', 'notice_period');
    }

    public function scopeWithHrProfile($query)
    {
        return $query->with(['profile', 'department']);
    }

    public function scopeExcludeAdmin($query)
    {
        return $query->where('role', '!=', 'admin');
    }
}
