<?php

namespace App\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'status', 'created_by'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
