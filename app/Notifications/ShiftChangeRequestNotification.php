<?php

namespace App\Notifications;

use App\Models\Shift\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftChangeRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $action: 'submitted' | 'approved' | 'rejected'
    public function __construct(
        private readonly ShiftChangeRequest $changeRequest,
        private readonly string $action,
        private readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cr        = $this->changeRequest->loadMissing('requester', 'currentShift', 'requestedShift');
        $requester = $cr->requester;
        $from      = $cr->currentShift->name ?? 'current shift';
        $to        = $cr->requestedShift->name ?? 'requested shift';
        $date      = $cr->effective_date->format('d M Y');

        return match ($this->action) {
            'submitted' => (new MailMessage)
                ->subject("Shift Change Request from {$requester->name}")
                ->greeting("Hello {$notifiable->name},")
                ->line("{$requester->name} has submitted a shift change request.")
                ->line("From: **{$from}** → To: **{$to}** effective {$date}")
                ->line("Reason: {$this->changeRequest->reason}")
                ->salutation('Regards, Staffee'),

            'approved' => (new MailMessage)
                ->subject('Your Shift Change Request Was Approved')
                ->greeting("Hello {$notifiable->name},")
                ->line("Your request to change from **{$from}** to **{$to}** (effective {$date}) has been **approved**.")
                ->salutation('Regards, HR Team'),

            'rejected' => (new MailMessage)
                ->subject('Your Shift Change Request Was Rejected')
                ->greeting("Hello {$notifiable->name},")
                ->line("Your request to change from **{$from}** to **{$to}** (effective {$date}) has been **rejected**.")
                ->lineIf((bool) $this->changeRequest->manager_notes, "Notes: {$this->changeRequest->manager_notes}")
                ->salutation('Regards, HR Team'),

            default => (new MailMessage)->subject('Shift Change Update'),
        };
    }

    public function toArray(object $notifiable): array
    {
        $cr        = $this->changeRequest->loadMissing('requester', 'currentShift', 'requestedShift');
        $requester = $cr->requester;
        $from      = $cr->currentShift->name ?? 'current shift';
        $to        = $cr->requestedShift->name ?? 'requested shift';

        $titles = [
            'submitted' => "Shift change request from {$requester->name}",
            'approved'  => "Your shift change to {$to} was approved",
            'rejected'  => "Your shift change to {$to} was rejected",
        ];

        return [
            'type'              => 'shift_change_request',
            'action'            => $this->action,
            'title'             => $titles[$this->action] ?? 'Shift change update',
            'body'              => "Change from {$from} to {$to} on {$this->changeRequest->effective_date->format('d M Y')}",
            'change_request_id' => $this->changeRequest->id,
            'actor_id'          => $this->actor->id,
            'url'               => null,
        ];
    }
}
