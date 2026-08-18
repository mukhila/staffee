<?php

namespace App\Models\Recruitment;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    protected $fillable = [
        'title',
        'department_id',
        'description',
        'requirements',
        'employment_type',
        'location',
        'salary_min',
        'salary_max',
        'openings',
        'status',
        'posted_by',
        'published_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closes_at'    => 'date',
            'salary_min'   => 'decimal:2',
            'salary_max'   => 'decimal:2',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_posting_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
