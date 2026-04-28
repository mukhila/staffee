<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $fillable = [
        'project_id', 'name', 'goal', 'start_date', 'end_date', 'status', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function completionPercentage(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        return (int) round($this->tasks()->where('status', 'completed')->count() / $total * 100);
    }

    public function velocity(): int
    {
        return $this->tasks()->where('status', 'completed')->count();
    }
}
