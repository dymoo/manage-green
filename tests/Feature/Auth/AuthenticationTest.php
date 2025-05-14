<?php

use App\Models\User;
// use Livewire\Volt\Volt as LivewireVolt; // Not for standard Livewire components
use Livewire\Livewire; // Use standard Livewire test helper
// use Filament\Testing\InteractsWithPages; // Remove this for now
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
// uses(InteractsWithPages::class); // Remove this for now

test('login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

it('users can authenticate using the login screen', function () {
    // Ensure the super_admin role exists globally
    Role::findOrCreate('super_admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('super_admin'); // Assign the role

    $component = Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate');

    $component->assertHasNoFormErrors();
    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    Livewire::test(\Filament\Pages\Auth\Login::class, ['panel' => 'admin'])
        ->set('data.email', $user->email)
        ->set('data.password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['data.email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});