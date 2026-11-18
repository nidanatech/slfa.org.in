<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user) => $user->isAdmin());
        Gate::define('access-directory', fn ($user) => $user->isAdmin() || $user->account_status === 'active');
        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return ($user->is_system_user && $user->system_role === 'admin') ? true : null;
        });

        Vite::prefetch(concurrency: 3);
    }
}
