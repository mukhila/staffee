<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat-channel.{channelId}', function ($user, $channelId) {
    return \App\Models\ChatChannel::find($channelId)
        ?->members()->where('user_id', $user->id)->exists() ?? false;
});
