<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailController extends Controller
{
    public function index()
    {
        $emails = Email::where('to_id', Auth::id())
            ->where('is_draft', false)
            ->with('from')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $unreadCount = Email::where('to_id', Auth::id())->whereNull('read_at')->where('is_draft', false)->count();

        return view('mail.index', compact('emails', 'unreadCount'));
    }

    public function unreadCount()
    {
        return response()->json(['count' => Email::where('to_id', Auth::id())->whereNull('read_at')->where('is_draft', false)->count()]);
    }

    public function sent()
    {
        $emails = Email::where('from_id', Auth::id())
            ->where('is_draft', false)
            ->with('to')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('mail.sent', compact('emails'));
    }

    public function drafts()
    {
        $emails = Email::drafts(Auth::id())->with('to')->orderBy('updated_at', 'desc')->paginate(10);
        return view('mail.drafts', compact('emails'));
    }

    public function create(Request $request)
    {
        $users         = User::where('id', '!=', Auth::id())->orderBy('name')->get();
        $prefill       = null;
        $parentEmail   = null;
        $composeType   = 'normal';

        if ($request->reply_to) {
            $parentEmail = Email::findOrFail($request->reply_to);
            abort_if($parentEmail->to_id !== Auth::id() && $parentEmail->from_id !== Auth::id(), 403);
            $composeType = 'reply';
            $prefill = [
                'to_id'   => $parentEmail->from_id,
                'subject' => 'Re: ' . $parentEmail->subject,
                'body'    => "\n\n---\nOn {$parentEmail->created_at->format('d M Y H:i')}, {$parentEmail->from->name} wrote:\n{$parentEmail->body}",
            ];
        } elseif ($request->forward) {
            $parentEmail = Email::findOrFail($request->forward);
            abort_if($parentEmail->to_id !== Auth::id() && $parentEmail->from_id !== Auth::id(), 403);
            $composeType = 'forward';
            $prefill = [
                'subject' => 'Fwd: ' . $parentEmail->subject,
                'body'    => "\n\n--- Forwarded Message ---\nFrom: {$parentEmail->from->name}\nDate: {$parentEmail->created_at->format('d M Y H:i')}\nSubject: {$parentEmail->subject}\n\n{$parentEmail->body}",
            ];
        } elseif ($request->draft) {
            $parentEmail = Email::where('id', $request->draft)->where('from_id', Auth::id())->where('is_draft', true)->firstOrFail();
            $prefill = [
                'to_id'   => $parentEmail->to_id,
                'subject' => $parentEmail->subject,
                'body'    => $parentEmail->body,
            ];
        }

        return view('mail.create', compact('users', 'prefill', 'parentEmail', 'composeType'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->boolean('is_draft');

        $request->validate([
            'to_id'   => $isDraft ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body'    => $isDraft ? 'nullable|string' : 'required|string',
        ]);

        $data = [
            'from_id'   => Auth::id(),
            'to_id'     => $request->to_id,
            'subject'   => $request->subject,
            'body'      => $request->body,
            'is_draft'  => $isDraft,
            'mail_type' => $request->mail_type ?? 'normal',
            'parent_id' => $request->parent_id,
        ];

        // If updating an existing draft
        if ($request->draft_id) {
            $draft = Email::where('id', $request->draft_id)->where('from_id', Auth::id())->where('is_draft', true)->firstOrFail();
            $draft->update($data);
            $email = $draft;
        } else {
            $email = Email::create($data);
        }

        if ($isDraft) {
            return response()->json(['success' => true, 'id' => $email->id]);
        }

        return redirect()->route('mail.index')->with('success', 'Email sent successfully.');
    }

    public function show(Email $email)
    {
        abort_if($email->to_id !== Auth::id() && $email->from_id !== Auth::id(), 403);

        if ($email->to_id == Auth::id() && !$email->read_at) {
            $email->update(['read_at' => now()]);
        }

        $email->load('from', 'to', 'parent.from', 'replies.from');

        return view('mail.show', compact('email'));
    }

    public function destroy(Email $email)
    {
        abort_if($email->from_id !== Auth::id() && $email->to_id !== Auth::id(), 403);
        $email->delete();
        return back()->with('success', 'Deleted.');
    }
}
