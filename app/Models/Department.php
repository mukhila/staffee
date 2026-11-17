<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use \App\Traits\Auditable;
    protected $fillable = ['name', 'description', 'is_active'];
    //
}
