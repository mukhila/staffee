<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active', 'department_id'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    //
}
