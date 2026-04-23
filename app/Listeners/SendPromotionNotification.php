<?php

namespace App\Listeners;

use App\Events\EmployeePromoted;
use App\Models\Notification;
use App\Models\User;

class SendPromotionNotification
{
    public function handle(EmployeePromoted $event): void
    {
        // Notify the promoted employee
        Notification::create([
            'user_id' => $event->employee->id,
            'type'    => 'promotion',
            'title'   => 'Congratulations on your Promotion!',
            'message' => "You have been promoted to {$event->promotion->proposed_designation}. Effective {$event->promotion->effective_date->format('d M Y')}.",
            'url'     => route('notifications.index'),
        ]);

        // Notify department colleagues (in same department, exclude admin)
        User::activeInDepartment($event->promotion->proposed_department_id)
            ->where('id', '!=', $event->employee->id)
            ->each(function (User $colleague) use ($event) {
                Notification::create([
                    'user_id' => $colleague->id,
                    'type'    => 'info',
                    'title'   => 'Team Update',
                    'message' => "{$event->employee->name} has been promoted to {$event->promotion->proposed_designation}.",
                    'url'     => route('notifications.index'),
                ]);
            });
    }
}
