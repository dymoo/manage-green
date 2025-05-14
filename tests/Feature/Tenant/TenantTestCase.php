<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Filament\Facades\Filament;
use App\Providers\Filament\AdminPanelProvider;
use Spatie\Permission\PermissionRegistrar;

class TenantTestCase extends TestCase
{
    protected Tenant $tenant;
    protected User $adminUser;
    protected User $staffUser;
    protected User $memberUser;

    protected function setUp(): void
    {
        parent::setUp(); // Call Laravel's base setup first

        // Create roles needed for tests AFTER parent::setUp()
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        // Clear permission cache after creating roles
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create the tenant
        $this->tenant = Tenant::factory()->create();

        // Create a default admin user for the tenant and authenticate
        $this->adminUser = User::factory()->admin($this->tenant)->create();
        $this->actingAs($this->adminUser); // Authenticate BEFORE setting tenant context

        // Set the current tenant for Filament AFTER authentication
        Filament::setTenant($this->tenant);

        // Create other default users (optional, can be done in specific tests)
        // $this->staffUser = $this->createStaffUser(); 
        // $this->memberUser = $this->createMemberUser();
    }

    // Helper method to create a staff user for the current tenant
    protected function createStaffUser(): User
    {
        // The staff() state in UserFactory handles role creation/assignment and tenant association.
        return User::factory()->staff($this->tenant)->create();
    }

     // Helper method to create a member user for the current tenant
    protected function createMemberUser(): User
    {
        // The member() state in UserFactory handles role creation/assignment and tenant association.
        return User::factory()->member($this->tenant)->create();
    }
} 