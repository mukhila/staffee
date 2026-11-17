<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A report emailed as a CSV attachment. Sent straight away (not queued) so the
 * attachment is never held in the queue table.
 */
class ScheduledReportNotification extends Notification
{
    use Queueable;

    /** @param  array{filename: string, csv: string, rows: int, summary: string[], range: string, title: string}  $report */
    public function __construct(public readonly array $report) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->report['title']}: {$this->report['range']}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$this->report['title']} report for {$this->report['range']} is attached ({$this->report['rows']} rows).");

        foreach ($this->report['summary'] as $line) {
            $mail->line($line);
        }

        return $mail
            ->action('Manage your scheduled reports', route('reports.scheduled.index'))
            ->line('You receive this because you subscribed to it in Staffee. You can turn it off on the page above.')
            ->attachData($this->report['csv'], $this->report['filename'], ['mime' => 'text/csv']);
    }
}
