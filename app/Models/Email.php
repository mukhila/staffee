<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'from_id', 'to_id', 'subject', 'body',
        'read_at', 'parent_id', 'is_draft', 'mail_type',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'read_at'  => 'datetime',
    ];

    public function from()    { return $this->belongsTo(User::class, 'from_id'); }
    public function to()      { return $this->belongsTo(User::class, 'to_id'); }
    public function parent()  { return $this->belongsTo(Email::class, 'parent_id'); }
    public function replies() { return $this->hasMany(Email::class, 'parent_id'); }

    public function scopeDrafts($query, int $userId)
    {
        return $query->where('from_id', $userId)->where('is_draft', true);
    }
}
