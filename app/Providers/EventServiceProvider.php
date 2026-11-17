<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Listeners in app/Listeners are discovered automatically from the event type
 * on their handle() method. Do not also list them in $listen: a listener in
 * both places is registered twice and runs twice (which sent every promotion,
 * resignation and termination notification two times).
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];
}
