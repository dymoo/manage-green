<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Redirect;
use App\Models\Tenant;
use App\Filament\Pages\Tenancy\TenantUserRegister;

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
    // Use the TenantUserRegister Filament page
    Route::get('/register', TenantUserRegister::class)->name('tenant.register');
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

require __DIR__.'/auth.php';
