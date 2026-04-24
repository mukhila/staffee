<?php

namespace App\Notifications\Leave;

use App\Models\Leave\LeaveBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowLeaveBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LeaveBalance $balance) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type      = $this->balance->leaveType;
        $available = $this->balance->effective_available;

        return (new MailMessage)
            ->subject("Low Leave Balance: {$type->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your **{$type->name}** balance is running low.")
            ->line("Available: **{$available} day(s)** remaining for {$this->balance->year}.")
            ->salutation('Regards, HR Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'low_leave_balance',
            'title'             => "Low {$this->balance->leaveType->name} balance",
            'body'              => "{$this->balance->effective_available} day(s) remaining",
            'leave_balance_id'  => $this->balance->id,
            'url'               => route('staff.leaves.index'),
        ];
    }
}
