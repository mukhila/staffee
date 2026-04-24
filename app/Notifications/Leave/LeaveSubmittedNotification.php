<?php

namespace App\Notifications\Leave;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly LeaveRequest $request,
        private readonly User $employee,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing('leaveType');
        return (new MailMessage)
            ->subject("Leave Request: {$this->employee->name} – {$req->type_label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->employee->name} has submitted a **{$req->type_label}** request.")
            ->line("Period: {$req->from_date->format('d M Y')} to {$req->to_date->format('d M Y')} ({$req->days} day(s))")
            ->line("Reason: {$req->reason}")
            ->action('Review Request', route('admin.leaves.show', $this->request))
            ->salutation('Regards, Staffee');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'leave_submitted',
            'title'            => "Leave request from {$this->employee->name}",
            'body'             => "{$this->request->days} day(s) of {$this->request->type_label}",
            'leave_request_id' => $this->request->id,
            'url'              => route('admin.leaves.show', $this->request),
        ];
    }
}
