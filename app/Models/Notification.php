<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'url', 'read_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public static function send(int $userId, string $type, string $title, string $message, ?string $url = null): self
    {
        return self::create(compact('userId', 'type', 'title', 'message', 'url') + ['user_id' => $userId]);
    }
}
