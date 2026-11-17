<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['from_id', 'to_id', 'body', 'read_at', 'attachment_path', 'attachment_type', 'attachment_name'];

    protected $hidden = ['attachment_path'];

    protected $appends = ['attachment_url'];

    /** Served through an authorised route, never as a public file. */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('chat.attachments.direct', $this->id) : null;
    }

    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }
}
