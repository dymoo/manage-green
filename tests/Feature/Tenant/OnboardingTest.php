<?php

use App\Models\User; // Assuming User model exists
use App\Models\Tenant; // Assuming Tenant model exists
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Providers\RouteServiceProvider;
use Livewire\Livewire;
use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Models\User as AppUser;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role; // Import Role

uses(RefreshDatabase::class);

// Test Suite for Club Onboarding

// test('club owner can sign up and create club') ...
// test('club settings can be updated') ...
// test('tenant can be accessed via subdomain') ... // This might need specific setup 

test('registration screen can be rendered', function () {
    // Tenant registration is usually at a fixed path relative to the panel
    $url = '/admin/new'; // Corrected URL
    
    // Ensure super-admin role exists
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']); // Corrected role name
    // Need to be authenticated to access panel routes, even registration
    $user = AppUser::factory()->create();
    $user->assignRole('super_admin'); // Corrected role name
    
    $response = $this->actingAs($user)->get($url);

    $response->assertStatus(200);
    $response->assertSeeLivewire(RegisterTenant::class);
});

test('new tenant can be registered', function () {
    config(['app.domain' => 'manage.test']); // Set a test central domain
    
    // Create a user who will register the tenant
    $user = AppUser::factory()->create();

    // Ensure the 'admin' role exists before the test runs
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']); 

    // Mail::fake(); // Fake mail if registration sends notifications

    // Act: Simulate registration via the Filament page
    Livewire::actingAs($user) // Authenticate the user FIRST
        ->test(RegisterTenant::class)
        ->fillForm([
            'name' => 'Test Club',
            'slug' => 'test-club',
            'country' => 'es',
            'timezone' => 'Europe/Madrid',
            // Add owner details
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testclub.com',
            'owner_password' => 'password',
            'owner_password_confirmation' => 'password',
            // Optional fields can be added if needed
            'use_custom_domain' => false,
        ])
        ->call('register') // Make sure 'register' is the correct action name
        ->assertHasNoErrors(); // Or assertRedirect()

    // Assert: Check if the tenant was created in the database
    $this->assertDatabaseHas('tenants', [
        'slug' => 'test-club',
        'name' => 'Test Club',
        'country' => 'es',
        'timezone' => 'Europe/Madrid',
        'use_custom_domain' => false,
    ]);

    // Assert: Check if the user is associated with the tenant
    $tenant = Tenant::whereSlug('test-club')->first();
    expect($tenant)->not->toBeNull();
    expect($tenant->users()->where('user_id', $user->id)->exists())->toBeTrue();

    // Assert: Check if the user was assigned the correct role within the tenant (if applicable)
    $this->assertTrue($user->hasRole('admin', $tenant)); // Check role within tenant context

    // Assert: Check if notification/email was sent (if using Mail::fake())
    // Mail::assertSent(...);
});

// Add test for validation errors
test('tenant registration fails with invalid data', function () {
    // config(['app.domain' => 'manage.test']); // Domain config might not be needed if using default subdomain behavior
    $user = AppUser::factory()->create(); // This user is just initiating, not the owner being created

    Livewire::actingAs($user)
        ->test(RegisterTenant::class)
        ->fillForm([
            'name' => '', // Invalid: name is required
            'slug' => 'test-club-invalid',
            'country' => 'es',
            'timezone' => 'Europe/Madrid',
            // Owner fields from BaseRegisterTenant
            'owner_name' => 'Test Owner Invalid',
            'owner_email' => 'not-an-email', // Invalid: not an email
            'owner_password' => 'password',
            'owner_password_confirmation' => 'wrong_password', // Invalid: passwords don't match
        ])
        ->call('register')
        ->assertHasFormErrors([
            'name' => 'required',
            'owner_email' => 'email', // Corrected key
            'owner_password' => 'confirmed',
        ]);
        // If the above fails, we would inspect the data structure manually by adding:
        // ->tap(fn ($component) => dd($component->getLivewireTestData()));
});

// Add test for unique slug constraint
test('tenant registration fails with duplicate slug', function () {
    config(['app.domain' => 'manage.test']);
    Tenant::factory()->create(['slug' => 'existing-club']);
    $user = AppUser::factory()->create();

    Livewire::actingAs($user)
        ->test(RegisterTenant::class)
        ->fillForm([
            'name' => 'Another Club',
            'slug' => 'existing-club',
            'country' => 'es',
            'timezone' => 'Europe/Madrid',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testclub.com',
            'owner_password' => 'password',
            'owner_password_confirmation' => 'password',
        ])
        ->call('register')
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('tenant registration requires a name', function () {
    $user = AppUser::factory()->create();

    Livewire::actingAs($user)
        ->test(RegisterTenant::class)
        ->fillForm([
            'name' => '',
            'slug' => 'valid-slug',
            'country' => 'es',
            'timezone' => 'Europe/Madrid',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testclub.com',
            'owner_password' => 'password',
            'owner_password_confirmation' => 'password',
            'use_custom_domain' => false,
        ])
        ->call('register')
        ->assertHasFormErrors(['name' => 'required']);
});

test('tenant registration requires a valid slug', function() {
    $user = AppUser::factory()->create();

    Livewire::actingAs($user)
        ->test(RegisterTenant::class)
        ->fillForm([
            'name' => 'Valid Name',
            'slug' => 'invalid slug spaces',
            'country' => 'es',
            'timezone' => 'Europe/Madrid',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testclub.com',
            'owner_password' => 'password',
            'owner_password_confirmation' => 'password',
            'use_custom_domain' => false,
        ])
        ->call('register')
        ->assertHasFormErrors(['slug' => 'alpha_dash']);
}); 