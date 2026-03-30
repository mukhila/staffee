<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTracker extends Model
{
    protected $fillable = ['user_id', 'trackable_id', 'trackable_type', 'start_time', 'end_time', 'description'];

    public function trackable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
