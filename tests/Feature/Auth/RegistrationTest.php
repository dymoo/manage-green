<?php

use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $this->markTestSkipped('Global public registration is not available. Tenant registration is at /{tenant}/register. Base domain registration is invite-only.');
    $response = $this->get('/register');

    $response->assertStatus(200);
})->skip();

test('new users can register', function () {
    $this->markTestSkipped('Global public registration is not available. Tenant registration is at /{tenant}/register. Base domain registration is invite-only.');
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response->assertRedirect(route('verification.notice', absolute: false));

    $this->assertAuthenticated();
})->skip();