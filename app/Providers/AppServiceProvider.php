<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Admin bypasses every Gate check before any policy or ability runs.
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        // Fallback: when no explicit Gate::define / Policy covers an ability,
        // check the user's role permissions. This means @can('create-staff'),
        // $user->can('view-tasks'), and $this->authorize('edit-project') all
        // resolve through HasPermissions::hasPermission() without having to
        // pre-register every slug at boot (which would require a DB query before
        // migrations run).
        Gate::after(function ($user, $ability, $result) {
            if ($result === null) {
                return $user->hasPermission($ability);
            }
        });
    }
}
