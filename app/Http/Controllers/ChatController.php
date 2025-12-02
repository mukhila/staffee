<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        // Get all users except current user
        $users = User::where('id', '!=', Auth::id())->get();
        return view('chat.index', compact('users'));
    }

    public function getMessages($userId)
    {
        $messages = Message::where(function($q) use ($userId) {
            $q->where('from_id', Auth::id())->where('to_id', $userId);
        })->orWhere(function($q) use ($userId) {
            $q->where('from_id', $userId)->where('to_id', Auth::id());
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'to_id' => 'required|exists:users,id',
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        if (!$request->body && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment is required'], 422);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat-attachments', 'public');
            $attachmentType = $file->getClientMimeType();
        }

        $message = Message::create([
            'from_id' => Auth::id(),
            'to_id' => $request->to_id,
            'body' => $request->body,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
