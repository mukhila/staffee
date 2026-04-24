<?php

namespace App\Notifications\Leave;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStartReminderNotification extends Notification implements ShouldQueue
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
            ->subject("Leave Starts Tomorrow: {$req->type_label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your **{$req->type_label}** leave begins **tomorrow**, {$req->from_date->format('d M Y')}.")
            ->line("Duration: {$req->days} day(s) until {$req->to_date->format('d M Y')}.")
            ->salutation('Regards, HR Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'leave_start_reminder',
            'title'            => "Your {$this->request->type_label} starts tomorrow",
            'body'             => "{$this->request->from_date->format('d M Y')} — {$this->request->days} day(s)",
            'leave_request_id' => $this->request->id,
            'url'              => route('staff.leaves.show', $this->request),
        ];
    }
}
