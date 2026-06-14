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
        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Tenant::class);
        \Laravel\Cashier\Cashier::useSubscriptionModel(\App\Models\Subscription::class);

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return ($user->is_system_user && $user->system_role === 'admin') ? true : null;
        });

        Vite::prefetch(concurrency: 3);

        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'contact' => \App\Models\Contact::class,
            'Contact' => \App\Models\Contact::class, // Support both for backward compatibility
            'company' => \App\Models\Company::class,
            'user' => \App\Models\User::class,
            'task' => \App\Models\Task::class,
            'note' => \App\Models\Note::class,
            'opportunity' => \App\Models\Opportunity::class,
            'Opportunity' => \App\Models\Opportunity::class, // Support capital O
            // Add other models as needed
        ]);
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Note::observe(\App\Observers\NoteObserver::class);
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Opportunity::observe(\App\Observers\OpportunityObserver::class);
        \App\Models\Event::observe(\App\Observers\EventObserver::class);
        \App\Models\AiBot::observe(\App\Observers\AiBotObserver::class);
        \App\Models\Tenant::observe(\App\Observers\TenantObserver::class);
    }
}
