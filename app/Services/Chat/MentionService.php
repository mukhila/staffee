<?php

namespace App\Services\Chat;

use App\Models\ChatChannelMessage;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "@Full Name" in a channel message notifies that channel member.
 */
class MentionService
{
    /**
     * Members named in the message (never the sender).
     *
     * @param  Collection<int, \App\Models\User>  $members
     * @return Collection<int, \App\Models\User>
     */
    public function mentioned(string $body, Collection $members, int $senderId): Collection
    {
        if (!str_contains($body, '@')) {
            return collect();
        }

        // Longest names first so "@Ann Lee" is not taken as "@Ann".
        $remaining = mb_strtolower($body);
        $found     = collect();

        foreach ($members->sortByDesc(fn ($m) => mb_strlen($m->name)) as $member) {
            $needle  = '@' . mb_strtolower($member->name);
            $pattern = '/' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u';

            if (preg_match($pattern, $remaining)) {
                $remaining = preg_replace($pattern, ' ', $remaining);
                if ($member->id !== $senderId) {
                    $found->push($member);
                }
            }
        }

        return $found->unique('id')->values();
    }

    /** Notify everyone mentioned. Returns how many were notified. */
    public function notify(ChatChannelMessage $message): int
    {
        $message->loadMissing('channel.members:id,name', 'user:id,name');

        $mentioned = $this->mentioned((string) $message->body, $message->channel->members, $message->user_id);

        foreach ($mentioned as $member) {
            Notification::create([
                'user_id' => $member->id,
                'type'    => 'chat_mention',
                'title'   => "{$message->user->name} mentioned you in #{$message->channel->name}",
                'message' => Str::limit((string) $message->body, 140),
                'url'     => route('chat.channels.show', $message->channel_id) . '#message-' . $message->id,
            ]);
        }

        return $mentioned->count();
    }
}
