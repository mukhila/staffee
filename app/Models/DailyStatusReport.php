<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DailyStatusReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_date',
        'task_name',
        'description',
        'start_time',
        'end_time',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
