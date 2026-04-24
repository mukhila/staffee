<?php

namespace App\Notifications\Leave;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveEndReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LeaveRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing('leaveType');
        return (new MailMessage)
            ->subject("Leave Ends Today: {$req->type_label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your **{$req->type_label}** leave ends **today**, {$req->to_date->format('d M Y')}.")
            ->line("You are expected to return to work tomorrow.")
            ->salutation('Regards, HR Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'leave_end_reminder',
            'title'            => "Your {$this->request->type_label} ends today",
            'body'             => "Return to work tomorrow",
            'leave_request_id' => $this->request->id,
            'url'              => route('staff.leaves.show', $this->request),
        ];
    }
}
