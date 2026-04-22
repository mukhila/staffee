<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Admin bypasses every Gate check automatically.
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        // Register every permission slug as a Gate ability so that
        // @can('create-staff'), $user->can('create-task'), etc. all work
        // without manually defining individual Gate::define() calls.
        $this->registerPermissionGates();
    }

    private function registerPermissionGates(): void
    {
        try {
            Permission::all()->each(function (Permission $permission) {
                Gate::define($permission->slug, function ($user) use ($permission) {
                    return $user->hasPermission($permission->slug);
                });
            });
        } catch (\Exception) {
            // DB might not exist yet (during fresh migrations).
        }
    }
}
