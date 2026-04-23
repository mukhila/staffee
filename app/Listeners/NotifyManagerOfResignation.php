<?php

namespace App\Listeners;

use App\Events\ResignationSubmitted;
use App\Models\Notification;

class NotifyManagerOfResignation
{
    public function handle(ResignationSubmitted $event): void
    {
        $resignation = $event->resignation;

        // Notify direct manager if set on the resignation
        if ($resignation->manager_id) {
            Notification::create([
                'user_id' => $resignation->manager_id,
                'type'    => 'warning',
                'title'   => 'Resignation Received',
                'message' => "{$event->employee->name} has submitted a resignation. Requested last date: {$resignation->requested_last_date->format('d M Y')}. Please review and respond.",
                'url'     => route('admin.hr.resignations.show', $resignation->id),
            ]);
        }

        // Acknowledge receipt to the employee
        Notification::create([
            'user_id' => $event->employee->id,
            'type'    => 'info',
            'title'   => 'Resignation Submitted',
            'message' => 'Your resignation has been received and is under review. Your manager will respond shortly.',
            'url'     => route('notifications.index'),
        ]);
    }
}
