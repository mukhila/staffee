<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatChannel extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'reference_id', 'description', 'created_by',
    ];

    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_channel_members')
            ->withPivot('last_read_message_id', 'last_read_at')
            ->withTimestamps();
    }

    public function messages() { return $this->hasMany(ChatChannelMessage::class, 'channel_id'); }

    /** Number of unread messages for a given user. */
    public function unreadCount(int $userId): int
    {
        $pivot = $this->members()->where('user_id', $userId)->first()?->pivot;
        $lastReadId = $pivot?->last_read_message_id ?? 0;
        return $this->messages()->where('id', '>', $lastReadId)->count();
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatChannelMessage::class, 'channel_id')->latestOfMany();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('members', fn ($q) => $q->where('user_id', $userId));
    }

    public static function slugify(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
    }
}
