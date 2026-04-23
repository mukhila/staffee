<?php

namespace App\Providers;

use App\Events\EmployeePromoted;
use App\Events\ResignationSubmitted;
use App\Events\TerminationInitiated;
use App\Listeners\NotifyHROfTermination;
use App\Listeners\NotifyManagerOfResignation;
use App\Listeners\SendPromotionNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EmployeePromoted::class => [
            SendPromotionNotification::class,
        ],
        TerminationInitiated::class => [
            NotifyHROfTermination::class,
        ],
        ResignationSubmitted::class => [
            NotifyManagerOfResignation::class,
        ],
    ];
}
