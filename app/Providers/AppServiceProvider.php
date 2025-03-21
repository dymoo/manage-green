<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
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
        // Add a custom route to handle the missing "filament.admin.pages.tenants" route
        Route::get('/admin/tenants', function () {
            return redirect('/admin');
        })->name('filament.admin.pages.tenants');

        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
