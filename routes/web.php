<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Redirect;
use App\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Redirect users to tenant panel upon login if they have access to tenants
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    if ($user && $user->tenants()->exists()) {
        $tenant = $user->tenants()->first();
        return redirect("/admin/tenants/{$tenant->slug}");
    }
    
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Tenant-specific routes
Route::prefix('{tenant}')->middleware(['web'])->group(function () {
    // Replace the TenantUserRegister with a closure that handles the tenant registration
    Route::get('/register', function (string $tenant) {
        // Validate the tenant exists
        $tenant = Tenant::where('slug', $tenant)->firstOrFail();
        
        // If user is logged in and has access to this tenant, redirect to dashboard
        if (auth()->check() && auth()->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
            return redirect("/admin/tenants/{$tenant->slug}");
        }
        
        // Create a regular registration form view for the tenant
        return view('auth.tenant-register', ['tenant' => $tenant]);
    })->name('tenant.register');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Fix the /admin/tenants 404 error by redirecting to tenants-overview
Route::get('/admin/tenants', function () {
    return Redirect::to('/admin/tenants-overview');
});

// Add a named route for login to satisfy the Authenticate middleware
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

require __DIR__.'/auth.php';
