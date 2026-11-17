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

        // For @mention suggestions in the message box.
        $mentionable = $channel->members->where('id', '!=', Auth::id())->pluck('name')->sort()->values();

        return view('chat.channel-show', compact('channel', 'messages', 'channels', 'mentionable'));
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
            'attachment' => \App\Services\Chat\ChatAttachmentService::validationRule(),
        ], [
            'attachment.mimes' => 'That file type cannot be sent. Use an image, PDF, Office document, text/CSV or ZIP file.',
            'attachment.max'   => 'Attachments must be 10 MB or smaller.',
        ]);

        $path = $name = $type = null;
        if ($request->hasFile('attachment')) {
            ['path' => $path, 'name' => $name, 'type' => $type] = app(\App\Services\Chat\ChatAttachmentService::class)
                ->store($request->file('attachment'), 'channels');
        }

        $msg = ChatChannelMessage::create([
            'channel_id'      => $channel->id,
            'user_id'         => Auth::id(),
            'body'            => $request->body,
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_type' => $type,
        ]);

        broadcast(new \App\Events\ChannelMessageSent($msg))->toOthers();

        if ($msg->body) {
            app(\App\Services\Chat\MentionService::class)->notify($msg);
        }

        return response()->json($msg->load('user'));
    }

    public function messages(Request $request, ChatChannel $channel)
    {
        abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);

        // ?after=ID returns only newer messages, so polling stays light.
        $after = (int) $request->query('after', 0);

        return response()->json(
            $channel->messages()->with('user:id,name')
                ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
                ->orderBy('id')
                ->get()
        );
    }

    /** Only channel members can open a channel attachment. */
    public function attachment(ChatChannelMessage $message)
    {
        abort_unless($message->channel->members()->where('user_id', Auth::id())->exists(), 404);
        abort_unless($message->attachment_path, 404);

        return app(\App\Services\Chat\ChatAttachmentService::class)
            ->respond($message->attachment_path, $message->attachment_name, $message->attachment_type);
    }

    public function join(ChatChannel $channel)
    {
        $user = Auth::user();

        if ($channel->is_private) {
            abort(403, 'This channel is invite-only.');
        }

        match ($channel->type) {
            'department' => abort_unless(
                $user->department_id && $channel->reference_id == $user->department_id,
                403, 'You do not belong to this department.'
            ),
            'project' => abort_unless(
                \App\Models\Project::where('id', $channel->reference_id)
                    ->whereHas('users', fn ($q) => $q->where('user_id', $user->id))
                    ->exists(),
                403, 'You are not assigned to this project.'
            ),
            default => null, // 'general' — open to all authenticated users
        };

        $channel->members()->syncWithoutDetaching([$user->id]);
        return back()->with('success', 'Joined channel.');
    }
}
