<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

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
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Gate::define('manage-inventory', function (User $user) {
            return in_array($user->role, ['admin', 'manager']);
        });

        Gate::define('manage-settings', function (User $user) {
            return $user->role === 'admin';
        });

        Gate::define('manage-users', function (User $user) {
            return $user->role === 'admin';
        });

        Gate::define('view-reports', function (User $user) {
            return in_array($user->role, ['admin', 'manager', 'accountant']);
        });

        Gate::define('manage-saas', function (User $user) {
            return $user->role === 'superadmin';
        });

        View::composer('*', function ($view) {
            if (!array_key_exists('baseCurrency', $view->getData()) && auth()->check()) {
                $view->with('baseCurrency', Currency::activeForTenant());
            }
        });
    }
}
