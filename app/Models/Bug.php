<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Models\User;

class Bug extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'assigned_to', 'reported_by', 'status', 'severity', 'test_case_id'];

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
}
