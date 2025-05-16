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

        // Create the tenant
        $this->tenant = Tenant::factory()->create();

        // Create roles needed for tests AFTER parent::setUp() and tenant creation
        // These roles should be tenant-specific by adding tenant_id
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id, // Associate role with tenant
        ]);
        
        $staffRole = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id, // Associate role with tenant
        ]);
        
        $memberRole = Role::firstOrCreate([
            'name' => 'member',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id, // Associate role with tenant
        ]);

        // Clear permission cache after creating roles
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create a default admin user for the tenant
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Associate admin with tenant and roles using actual model relationships
        $this->adminUser->tenants()->attach($this->tenant->id);
        $this->adminUser->roles()->attach($adminRole->id, [
            'model_type' => User::class,
            'team_id' => $this->tenant->id, // Set team_id (used by Spatie Permission)
            'tenant_id' => $this->tenant->id, // Set tenant_id (custom field)
        ]);
        
        // Authenticate as admin
        $this->actingAs($this->adminUser);

        // Set the current tenant for Filament AFTER authentication
        Filament::setTenant($this->tenant);
    }

    // Helper method to create a staff user for the current tenant
    protected function createStaffUser(): User
    {
        $staffRole = Role::where([
            'name' => 'staff',
            'tenant_id' => $this->tenant->id,
        ])->first();
        
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        $user->tenants()->attach($this->tenant->id);
        $user->roles()->attach($staffRole->id, [
            'model_type' => User::class,
            'team_id' => $this->tenant->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        return $user;
    }

     // Helper method to create a member user for the current tenant
    protected function createMemberUser(): User
    {
        $memberRole = Role::where([
            'name' => 'member',
            'tenant_id' => $this->tenant->id,
        ])->first();
        
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        $user->tenants()->attach($this->tenant->id);
        $user->roles()->attach($memberRole->id, [
            'model_type' => User::class,
            'team_id' => $this->tenant->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Create wallet for the member
        $user->ensureWalletExists();
        
        return $user;
    }
} 