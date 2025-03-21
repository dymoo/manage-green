<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run our test
echo "Starting test...\n";

// Ensure we have users and roles
$users = \App\Models\User::all();
echo "Found " . $users->count() . " users\n";

$roles = \Spatie\Permission\Models\Role::all();
echo "Found " . $roles->count() . " roles\n";

// Create a tenant
$tenant = \App\Models\Tenant::updateOrCreate(
    ['slug' => 'test-tenant'],
    ['name' => 'Test Tenant']
);
echo "Created tenant: " . $tenant->name . "\n";

// Create a test user
$user = \App\Models\User::updateOrCreate(
    ['email' => 'test@manage.green'],
    [
        'name' => 'Test User',
        'password' => Hash::make('password'),
    ]
);
echo "Created user: " . $user->name . "\n";

// Associate user with tenant
if (!$tenant->users()->where('user_id', $user->id)->exists()) {
    $tenant->users()->attach($user->id);
    echo "Associated user with tenant\n";
}

// Assign a role to the user for this tenant
$user->assignRole('admin', $tenant);
echo "Assigned admin role to user for tenant\n";

// Test if the user has the role
$hasRole = $user->hasRole('admin', $tenant);
echo "User has admin role in tenant: " . ($hasRole ? 'Yes' : 'No') . "\n";

// Get all roles for this user in the tenant
$roleNames = $user->getRoleNames($tenant);
echo "User roles in tenant: " . $roleNames->implode(', ') . "\n";

echo "Test completed\n"; 