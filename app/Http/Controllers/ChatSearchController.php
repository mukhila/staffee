<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatChannelMessage;
use App\Models\Message;
use Illuminate\Http\Request;

/**
 * Search your own conversations: direct messages you sent or received,
 * and channels you are a member of.
 */
class ChatSearchController extends Controller
{
    private const LIMIT = 50;

    public function index(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $q    = trim($data['q'] ?? '');
        $me   = $request->user()->id;

        $direct = collect();
        $channel = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%' . addcslashes($q, '%_\\') . '%';

            $direct = Message::with(['from:id,name', 'to:id,name'])
                ->where(fn ($w) => $w->where('from_id', $me)->orWhere('to_id', $me))
                ->where(fn ($w) => $w->where('body', 'like', $like)->orWhere('attachment_name', 'like', $like))
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get();

            $channelIds = ChatChannel::forUser($me)->pluck('id');

            $channel = ChatChannelMessage::with(['user:id,name', 'channel:id,name'])
                ->whereIn('channel_id', $channelIds)
                ->where(fn ($w) => $w->where('body', 'like', $like)->orWhere('attachment_name', 'like', $like))
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->get();
        }

        return view('chat.search', [
            'q'       => $q,
            'direct'  => $direct,
            'channel' => $channel,
            'me'      => $me,
            'limit'   => self::LIMIT,
        ]);
    }
}
