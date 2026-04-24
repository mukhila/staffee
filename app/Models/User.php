<?php

namespace App\Models;

use App\Models\HR\EmployeeProfile;
use App\Models\HR\LifecycleEvent;
use App\Models\HR\PromotionRequest;
use App\Models\HR\ResignationRequest;
use App\Models\HR\SalaryRevision;
use App\Models\HR\TerminationRequest;
use App\Models\HR\WarningRecord;
use App\Models\Payroll\EmployeeSalaryStructure;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollRunEmployee;
use App\Models\Payroll\PayrollSlip;
use App\Models\Payroll\SalaryRevisionRequest;
use App\Models\Monitoring\MonitoringSession;
use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPermissions;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'address',
        'department_id', 'reporting_to', 'is_active', 'avatar', 'agent_token',
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

    public function salaryStructures()
    {
        return $this->hasMany(EmployeeSalaryStructure::class)->orderByDesc('effective_from');
    }

    public function activeSalaryStructure()
    {
        return $this->hasOne(EmployeeSalaryStructure::class)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', today()->toDateString())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', today()->toDateString());
            });
    }

    public function payrollSlips()
    {
        return $this->hasMany(PayrollSlip::class)->orderByDesc('period_end');
    }

    public function payrollAdjustments()
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function payrollRunEntries()
    {
        return $this->hasMany(PayrollRunEmployee::class);
    }

    public function salaryRevisionRequests()
    {
        return $this->hasMany(SalaryRevisionRequest::class)->orderByDesc('effective_date');
    }

    // ── Shift relationships ───────────────────────────────────────────────────

    public function shiftAssignments()
    {
        return $this->hasMany(\App\Models\Shift\ShiftAssignment::class);
    }

    public function currentShiftAssignment()
    {
        return $this->hasOne(\App\Models\Shift\ShiftAssignment::class)
            ->where('status', 'active')
            ->where('effective_from', '<=', today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', today()->toDateString());
            });
    }

    public function attendanceExceptions()
    {
        return $this->hasMany(\App\Models\Shift\AttendanceException::class);
    }

    public function shiftChangeRequests()
    {
        return $this->hasMany(\App\Models\Shift\ShiftChangeRequest::class, 'requester_id');
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

    // ─── Monitoring ───────────────────────────────────────────────────────────

    public function monitoringSessions()
    {
        return $this->hasMany(MonitoringSession::class);
    }

    public function activeMonitoringSession(): ?MonitoringSession
    {
        return $this->monitoringSessions()
            ->where('status', 'active')
            ->where('last_heartbeat_at', '>=', now()->subMinutes(3))
            ->latest('last_heartbeat_at')
            ->first();
    }

    public function isOnline(): bool
    {
        return $this->activeMonitoringSession() !== null;
    }

    public function generateAgentToken(): string
    {
        $token = bin2hex(random_bytes(32)); // 64-char hex
        $this->update(['agent_token' => $token]);
        return $token;
    }

    public function revokeAgentToken(): void
    {
        $this->update(['agent_token' => null]);
    }

    public function getAvatarInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
