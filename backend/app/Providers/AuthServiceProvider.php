<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (! str_contains($ability, '.')) {
                return null;
            }

            if ($user->isInstanceAdmin()) {
                return true;
            }

            if ($user->hasRoleCode('system_admin')) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}
