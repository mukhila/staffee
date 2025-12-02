<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Email;
use Illuminate\Support\Facades\Auth;

class MailController extends Controller
{
    public function index()
    {
        $emails = Email::where('to_id', Auth::id())
            ->with('from')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('mail.index', compact('emails'));
    }

    public function sent()
    {
        $emails = Email::where('from_id', Auth::id())
            ->with('to')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('mail.sent', compact('emails'));
    }

    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('mail.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string'
        ]);

        Email::create([
            'from_id' => Auth::id(),
            'to_id' => $request->to_id,
            'subject' => $request->subject,
            'body' => $request->body
        ]);

        return redirect()->route('mail.index')->with('success', 'Email sent successfully.');
    }

    public function show(Email $email)
    {
        if ($email->to_id != Auth::id() && $email->from_id != Auth::id()) {
            abort(403);
        }

        if ($email->to_id == Auth::id() && !$email->read_at) {
            $email->update(['read_at' => now()]);
        }

        return view('mail.show', compact('email'));
    }
}
