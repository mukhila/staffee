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

    protected $hidden = ['attachment_path'];

    protected $appends = ['attachment_url'];

    /** Served through an authorised route, never as a public file. */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('chat.attachments.channel', $this->id) : null;
    }
}
