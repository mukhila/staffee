<?php

namespace App\Models\HR;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LifecycleEvent extends Model
{
    protected $table = 'lifecycle_events';

    protected $fillable = [
        'user_id', 'event_type', 'title', 'description', 'effective_date',
        'old_role', 'new_role',
        'old_department_id', 'new_department_id',
        'old_designation', 'new_designation',
        'old_salary', 'new_salary',
        'reference_type', 'reference_id',
        'performed_by', 'is_sensitive', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_sensitive'   => 'boolean',
            'metadata'       => 'array',
            'old_salary'     => 'decimal:2',
            'new_salary'     => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function oldDepartment()
    {
        return $this->belongsTo(Department::class, 'old_department_id');
    }

    public function newDepartment()
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    // Polymorphic back-link to the detail record
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        $class = 'App\\Models\\HR\\' . class_basename($this->reference_type);
        return $class::find($this->reference_id);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeVisible($query)
    {
        return $query->where('is_sensitive', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->orderByDesc('effective_date');
    }
}
