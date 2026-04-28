<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Models\User;

class Bug extends Model
{
    // Valid status transitions: from → [allowed to]
    public const TRANSITIONS = [
        'open'        => ['in_progress', 'closed'],
        'in_progress' => ['resolved', 'open'],
        'resolved'    => ['closed', 'in_progress'],
        'closed'      => [],
    ];

    // Statuses that require resolution_notes
    public const REQUIRES_NOTES = ['resolved', 'closed'];

    protected $fillable = [
        'project_id', 'title', 'description', 'assigned_to', 'reported_by',
        'status', 'severity', 'priority', 'test_case_id',
        'resolution_notes', 'resolved_at', 'closed_at', 'resolved_by',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? []);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reportedByUser()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
