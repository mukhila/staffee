<?php

namespace App\Http\Controllers;

use App\Models\ChatChannel;
use App\Models\ChatChannelMessage;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatChannelController extends Controller
{
    public function index()
    {
        $channels    = ChatChannel::forUser(Auth::id())->with('latestMessage')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $projects    = Project::orderBy('name')->get();
        $allUsers    = User::where('id', '!=', Auth::id())->orderBy('name')->get();

        return view('chat.channels', compact('channels', 'departments', 'projects', 'allUsers'));
    }

    public function show(ChatChannel $channel)
    {
        abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);

        $messages = $channel->messages()->with('user')->orderBy('created_at', 'asc')->get();
        $channels = ChatChannel::forUser(Auth::id())->with('latestMessage')->orderBy('name')->get();

        // Mark all messages as read for current user
        $lastMsg = $messages->last();
        if ($lastMsg) {
            $channel->members()->updateExistingPivot(Auth::id(), [
                'last_read_message_id' => $lastMsg->id,
                'last_read_at'         => now(),
            ]);
        }

        return view('chat.channel-show', compact('channel', 'messages', 'channels'));
    }

    public function markRead(ChatChannel $channel)
    {
        abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);

        $lastMsg = $channel->messages()->latest()->first();
        if ($lastMsg) {
            $channel->members()->updateExistingPivot(Auth::id(), [
                'last_read_message_id' => $lastMsg->id,
                'last_read_at'         => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:general,department,project',
            'description' => 'nullable|string|max:255',
            'members'     => 'array',
            'members.*'   => 'exists:users,id',
            'reference_id' => 'nullable|integer',
        ]);

        $slug = ChatChannel::slugify($request->name);
        $base = $slug;
        $i = 1;
        while (ChatChannel::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $channel = ChatChannel::create([
            'name'         => $request->name,
            'slug'         => $slug,
            'type'         => $request->type,
            'reference_id' => $request->reference_id,
            'description'  => $request->description,
            'created_by'   => Auth::id(),
        ]);

        $members = collect($request->members ?? [])->push(Auth::id())->unique()->toArray();
        $channel->members()->syncWithoutDetaching($members);

        return redirect()->route('chat.channels.show', $channel)->with('success', 'Channel created.');
    }

    public function sendMessage(Request $request, ChatChannel $channel)
    {
        abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);

        $request->validate([
            'body'       => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $path = $name = $type = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('channel-attachments', 'public');
            $name = $file->getClientOriginalName();
            $type = $file->getClientMimeType();
        }

        $msg = ChatChannelMessage::create([
            'channel_id'      => $channel->id,
            'user_id'         => Auth::id(),
            'body'            => $request->body,
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_type' => $type,
        ]);

        return response()->json($msg->load('user'));
    }

    public function messages(ChatChannel $channel)
    {
        abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);
        return response()->json(
            $channel->messages()->with('user')->orderBy('created_at', 'asc')->get()
        );
    }

    public function join(ChatChannel $channel)
    {
        $channel->members()->syncWithoutDetaching([Auth::id()]);
        return back()->with('success', 'Joined channel.');
    }
}
