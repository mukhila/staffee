<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'project_id', 'title', 'description', 'due_date', 'status', 'created_by', 'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'completed';
    }

    public function completionPercentage(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        return (int) round($this->tasks()->where('status', 'completed')->count() / $total * 100);
    }
}
