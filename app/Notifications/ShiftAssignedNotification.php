<?php

namespace App\Notifications;

use App\Models\Shift\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ShiftAssignment $assignment,
        private readonly bool $isReminder = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shift = $this->assignment->loadMissing('shift')->shift;
        $from  = $this->assignment->effective_from->format('d M Y');
        $subject = $this->isReminder
            ? "Shift Reminder: {$shift->name} tomorrow"
            : "You have been assigned to {$shift->name}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},");

        if ($this->isReminder) {
            $message->line("This is a reminder that you are scheduled for **{$shift->name}** tomorrow.");
        } else {
            $message->line("You have been assigned to the **{$shift->name}** shift effective {$from}.");
        }

        return $message
            ->line("Shift hours: {$shift->start_time} – {$shift->end_time}")
            ->line("If you have any questions, please contact HR.")
            ->salutation('Regards, HR Team');
    }

    public function toArray(object $notifiable): array
    {
        $shift = $this->assignment->loadMissing('shift')->shift;
        $from  = $this->assignment->effective_from->format('d M Y');

        return [
            'type'        => $this->isReminder ? 'shift_reminder' : 'shift_assigned',
            'title'       => $this->isReminder
                ? "Shift reminder: {$shift->name} tomorrow"
                : "Assigned to {$shift->name} from {$from}",
            'body'        => $this->isReminder
                ? "You are scheduled for the {$shift->name} shift ({$shift->start_time}–{$shift->end_time}) tomorrow."
                : "You have been assigned to {$shift->name} ({$shift->start_time}–{$shift->end_time}) starting {$from}.",
            'shift_id'    => $shift->id,
            'shift_name'  => $shift->name,
            'effective_from' => $from,
            'url'         => null,
        ];
    }
}
