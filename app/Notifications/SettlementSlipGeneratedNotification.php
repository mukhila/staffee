<?php

namespace App\Notifications;

use App\Models\HR\FinalSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementSlipGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FinalSettlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Full & final settlement generated')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your full and final settlement has been generated.')
            ->line("Net payable: {$this->settlement->net_payable} {$this->settlement->currency}")
            ->salutation('Regards, Staffee Payroll');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'settlement_slip_generated',
            'title' => 'Settlement slip generated',
            'body' => 'Your full and final settlement slip has been generated.',
            'settlement_id' => $this->settlement->id,
            'net_payable' => $this->settlement->net_payable,
            'url' => null,
        ];
    }
}
