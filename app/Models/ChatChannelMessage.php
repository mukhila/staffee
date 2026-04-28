<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatChannelMessage extends Model
{
    protected $fillable = [
        'channel_id', 'user_id', 'body',
        'attachment_path', 'attachment_name', 'attachment_type',
    ];

    public function channel() { return $this->belongsTo(ChatChannel::class); }
    public function user()    { return $this->belongsTo(User::class); }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::url($this->attachment_path) : null;
    }
}
