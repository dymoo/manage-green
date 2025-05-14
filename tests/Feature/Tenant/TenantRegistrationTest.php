<?php

namespace Tests\Feature\Tenant;

use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Filament\Facades\Filament;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdminUser(): User
    {
        $user = User::factory()->create();
        // Ensure the super_admin role exists globally
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($superAdminRole); // AssignRole from HasTenantPermissions should handle global if no tenant passed
        return $user;
    }

    public function test_tenant_registration_page_can_be_rendered_by_super_admin_user(): void
    {
        $superAdmin = $this->createSuperAdminUser();
        $url = '/admin/new'; // Corrected URL based on route:list output
        $response = $this->actingAs($superAdmin)->get($url);
        $response->assertSuccessful();
        $response->assertSeeLivewire(RegisterTenant::class);
    }

    public function test_tenant_can_be_registered_by_super_admin_user(): void
    {
        $superAdmin = $this->createSuperAdminUser();
        Role::findOrCreate('admin', 'web'); // Ensure 'admin' role exists for tenant assignment

        Livewire::actingAs($superAdmin)
            ->test(RegisterTenant::class)
            ->fillForm([
                'name' => 'New Club Name',
                'slug' => 'new-club-slug',
                'country' => 'es',
                'currency' => 'EUR',
                'timezone' => 'Europe/Madrid',
                'use_custom_domain' => false,
                'enable_wallet' => true,
                'enable_inventory' => true,
                'enable_pos' => true,
                'enable_member_portal' => false,
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tenants', ['slug' => 'new-club-slug']);
        
        $tenant = Tenant::whereSlug('new-club-slug')->firstOrFail();
        $this->assertTrue($tenant->users->contains($superAdmin)); // Ensure superAdmin is attached to tenant users
        
        // Assert that the superAdmin (who registered the tenant) has the 'admin' role in this new tenant
        $this->assertTrue($superAdmin->fresh()->hasRole('admin', $tenant));
    }

    public function test_tenant_registration_requires_valid_name_and_slug(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('admin', 'web');

        Livewire::actingAs($user)
            ->test(RegisterTenant::class)
            ->fillForm([
                'name' => '',
                'slug' => 'invalid slug with spaces',
                'country' => 'es',
                'currency' => 'EUR',
                'timezone' => 'Europe/Madrid',
                'use_custom_domain' => false,
                'enable_wallet' => true,
                'enable_inventory' => true,
                'enable_pos' => true,
                'enable_member_portal' => false,
            ])
            ->call('register')
            ->assertHasFormErrors(['name', 'slug']);
    }

    public function test_tenant_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('admin', 'web');

        Tenant::factory()->create(['slug' => 'existing-slug']);

        Livewire::actingAs($user)
            ->test(RegisterTenant::class)
            ->fillForm([
                'name' => 'Another Club',
                'slug' => 'existing-slug',
                'country' => 'es',
                'currency' => 'EUR',
                'timezone' => 'Europe/Madrid',
                'use_custom_domain' => false,
                'enable_wallet' => true,
                'enable_inventory' => true,
                'enable_pos' => true,
                'enable_member_portal' => false,
            ])
            ->call('register')
            ->assertHasFormErrors(['slug' => 'unique']);
    }
}