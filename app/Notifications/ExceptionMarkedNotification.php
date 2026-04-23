<?php

namespace App\Notifications;

use App\Models\Shift\AttendanceException;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExceptionMarkedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AttendanceException $exception,
        private readonly User $employee,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->exception->type_label;
        $date  = $this->exception->date->format('d M Y');
        $name  = $this->employee->name;

        return (new MailMessage)
            ->subject("Attendance Exception: {$label} for {$name} on {$date}")
            ->greeting("Hello {$notifiable->name},")
            ->line("An attendance exception has been automatically detected for **{$name}**.")
            ->line("Exception: **{$label}** on {$date}")
            ->lineIf($this->exception->deviation_minutes > 0,
                "Deviation: {$this->exception->deviation_minutes} minutes")
            ->line("Please review this exception in the shift management panel.")
            ->salutation('Regards, Staffee');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'attendance_exception',
            'title'        => "{$this->exception->type_label} detected for {$this->employee->name}",
            'body'         => "On {$this->exception->date->format('d M Y')} – {$this->exception->deviation_minutes} min deviation",
            'exception_id' => $this->exception->id,
            'employee_id'  => $this->employee->id,
            'exception_type' => $this->exception->exception_type,
            'url'          => null,
        ];
    }
}
