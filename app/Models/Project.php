<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'start_date', 'end_date', 'status', 'created_by',
        'documents', 'estimation_time'
    ];

    protected $casts = [
        'documents' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
