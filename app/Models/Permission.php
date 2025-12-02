<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role; // Added for the roles relationship

class Permission extends Model
{
    protected $fillable = ['name', 'slug'];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
