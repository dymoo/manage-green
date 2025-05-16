<?php

namespace App\Providers;

use App\Livewire\TenantRegisterForm;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        // Register Livewire components
        Livewire::component('tenant-register-form', TenantRegisterForm::class);
        
        // Add back the redirection for tenants
        Route::get('/admin/tenants', function () {
            return redirect('/admin/tenants-overview');
        })->name('filament.admin.pages.tenants');

        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
