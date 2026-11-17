<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ChatAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function __construct(private readonly ChatAttachmentService $attachments) {}

    public function index()
    {
        // Everyone you can message: active colleagues, not yourself.
        $users = User::where('id', '!=', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('users'));
    }

    public function getMessages($userId)
    {
        $me = Auth::id();

        $messages = Message::where(function ($q) use ($me, $userId) {
            $q->where(fn ($a) => $a->where('from_id', $me)->where('to_id', $userId))
              ->orWhere(fn ($b) => $b->where('from_id', $userId)->where('to_id', $me));
        })->orderBy('created_at', 'asc')->get();

        // Mark incoming messages as read
        Message::where('from_id', $userId)->where('to_id', $me)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    public function unreadCount()
    {
        $count = Message::where('to_id', Auth::id())->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'to_id'      => 'required|exists:users,id',
            'body'       => 'nullable|string|max:5000',
            'attachment' => ChatAttachmentService::validationRule(),
        ], [
            'attachment.mimes' => 'That file type cannot be sent. Use an image, PDF, Office document, text/CSV or ZIP file.',
            'attachment.max'   => 'Attachments must be 10 MB or smaller.',
        ]);

        if (!$request->filled('body') && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment is required'], 422);
        }

        $file = $request->hasFile('attachment')
            ? $this->attachments->store($request->file('attachment'), 'direct')
            : null;

        $message = Message::create([
            'from_id'         => Auth::id(),
            'to_id'           => $request->to_id,
            'body'            => $request->body,
            'attachment_path' => $file['path'] ?? null,
            'attachment_name' => $file['name'] ?? null,
            'attachment_type' => $file['type'] ?? null,
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message);
    }

    /** Only the sender and the recipient can open a direct-message attachment. */
    public function attachment(Message $message)
    {
        abort_unless(in_array((int) Auth::id(), [(int) $message->from_id, (int) $message->to_id], true), 404);
        abort_unless($message->attachment_path, 404);

        return $this->attachments->respond($message->attachment_path, $message->attachment_name, $message->attachment_type);
    }
}
