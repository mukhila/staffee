<?php

namespace App\Notifications;

use App\Models\Payroll\PayrollSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollSlipReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PayrollSlip $slip) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payroll slip ready: {$this->slip->period_start->format('M Y')}")
            ->greeting("Hello {$notifiable->name},")
            ->line('Your payroll slip is now available.')
            ->line("Net pay: {$this->slip->net_pay} {$this->slip->currency_code}")
            ->action('View Slip', route('payroll.slips.show', $this->slip))
            ->salutation('Regards, Staffee Payroll');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payroll_slip_ready',
            'title' => 'Payroll slip ready',
            'body' => "Your payroll slip for {$this->slip->period_start->format('M Y')} is available.",
            'slip_id' => $this->slip->id,
            'net_pay' => $this->slip->net_pay,
            'url' => route('payroll.slips.show', $this->slip),
        ];
    }
}
