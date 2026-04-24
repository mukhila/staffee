<?php

namespace App\Notifications;

use App\Models\Payroll\PayrollRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PayrollRun $run) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payroll processed: {$this->run->for_month}/{$this->run->for_year}")
            ->greeting("Hello {$notifiable->name},")
            ->line('Payroll processing has completed successfully.')
            ->line("Employees processed: {$this->run->slips()->count()}")
            ->salutation('Regards, Staffee Payroll');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payroll_processed',
            'title' => 'Payroll processed',
            'body' => "Payroll for {$this->run->for_month}/{$this->run->for_year} has been processed.",
            'run_id' => $this->run->id,
            'status' => $this->run->status,
            'url' => route('admin.payroll.runs.status', $this->run),
        ];
    }
}
