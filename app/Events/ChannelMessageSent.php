<?php

namespace App\Events;

use App\Models\ChatChannelMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatChannelMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat-channel.' . $this->message->channel_id)];
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message->load('user')];
    }
}
