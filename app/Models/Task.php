<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'sprint_id', 'milestone_id',
        'title', 'description', 'assigned_to', 'status', 'due_date', 'start_date',
    ];

    protected $casts = [
        'due_date'   => 'date',
        'start_date' => 'date',
    ];

    public function project()      { return $this->belongsTo(Project::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function comments()     { return $this->hasMany(TaskComment::class); }
    public function sprint()       { return $this->belongsTo(Sprint::class); }
    public function milestone()    { return $this->belongsTo(Milestone::class); }

    /** Tasks that must be completed before this task can start. */
    public function blockers()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'blocked_id', 'blocker_id');
    }

    /** Tasks that are blocked until this task is completed. */
    public function blocking()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'blocker_id', 'blocked_id');
    }

    public function isBlocked(): bool
    {
        return $this->blockers()
            ->where('status', '!=', 'completed')
            ->exists();
    }
}
