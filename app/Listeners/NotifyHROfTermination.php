<?php

namespace App\Listeners;

use App\Events\TerminationInitiated;
use App\Models\Notification;
use App\Models\User;

class NotifyHROfTermination
{
    public function handle(TerminationInitiated $event): void
    {
        // Notify all admins (HR) of the pending termination
        User::where('role', 'admin')->each(function (User $admin) use ($event) {
            if ($admin->id === $event->initiatedBy->id) return;

            Notification::create([
                'user_id' => $admin->id,
                'type'    => 'warning',
                'title'   => 'Termination Request',
                'message' => "A termination has been initiated for {$event->employee->name} by {$event->initiatedBy->name}.",
                'url'     => route('admin.hr.terminations.show', $event->termination->id),
            ]);
        });

        // Notify the employee
        Notification::create([
            'user_id' => $event->employee->id,
            'type'    => 'warning',
            'title'   => 'Employment Termination Notice',
            'message' => "A termination process has been initiated for your employment. Last working date: {$event->termination->last_working_date->format('d M Y')}. Please contact HR.",
            'url'     => route('notifications.index'),
        ]);
    }
}
