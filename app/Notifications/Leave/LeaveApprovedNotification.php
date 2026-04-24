<?php

namespace App\Notifications\Leave;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly LeaveRequest $request,
        private readonly ?User $approver,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing('leaveType');
        return (new MailMessage)
            ->subject("Leave Approved: {$req->type_label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your **{$req->type_label}** request has been **approved**.")
            ->line("Period: {$req->from_date->format('d M Y')} to {$req->to_date->format('d M Y')} ({$req->days} day(s))")
            ->salutation('Regards, HR Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'leave_approved',
            'title'            => "Your {$this->request->type_label} was approved",
            'body'             => "{$this->request->days} day(s) from {$this->request->from_date->format('d M Y')}",
            'leave_request_id' => $this->request->id,
            'url'              => route('staff.leaves.show', $this->request),
        ];
    }
}
