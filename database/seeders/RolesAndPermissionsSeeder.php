<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        // Tenant permissions
        Permission::create(['name' => 'create_tenant']);
        Permission::create(['name' => 'edit_tenant']);
        Permission::create(['name' => 'delete_tenant']);
        Permission::create(['name' => 'view_any_tenant']);
        Permission::create(['name' => 'view_tenant']);

        // User permissions
        Permission::create(['name' => 'create_user']);
        Permission::create(['name' => 'edit_user']);
        Permission::create(['name' => 'delete_user']);
        Permission::create(['name' => 'view_any_user']);
        Permission::create(['name' => 'view_user']);
        
        // Member permissions
        Permission::create(['name' => 'create_member']);
        Permission::create(['name' => 'edit_member']);
        Permission::create(['name' => 'delete_member']);
        Permission::create(['name' => 'view_any_member']);
        Permission::create(['name' => 'view_member']);
        
        // Inventory permissions
        Permission::create(['name' => 'create_inventory']);
        Permission::create(['name' => 'edit_inventory']);
        Permission::create(['name' => 'delete_inventory']);
        Permission::create(['name' => 'view_any_inventory']);
        Permission::create(['name' => 'view_inventory']);
        
        // Sales permissions
        Permission::create(['name' => 'create_sale']);
        Permission::create(['name' => 'view_any_sale']);
        Permission::create(['name' => 'view_sale']);
        
        // Report permissions
        Permission::create(['name' => 'view_reports']);
        Permission::create(['name' => 'export_reports']);

        // Create global roles and assign permissions
        
        // Super Admin - has all permissions across all tenants
        // This is a global role, not tenant-specific
        $superAdminRole = Role::create(['name' => 'super_admin', 'tenant_id' => null]);
        $superAdminRole->givePermissionTo(Permission::all());
        
        // Create tenant-specific roles (these will be assigned per tenant)
        
        // Admin - has all permissions within their tenant
        $adminRole = Role::create(['name' => 'admin', 'tenant_id' => null]);
        $adminRole->givePermissionTo([
            'edit_tenant', 'view_tenant',
            'create_user', 'edit_user', 'delete_user', 'view_any_user', 'view_user',
            'create_member', 'edit_member', 'delete_member', 'view_any_member', 'view_member',
            'create_inventory', 'edit_inventory', 'delete_inventory', 'view_any_inventory', 'view_inventory',
            'create_sale', 'view_any_sale', 'view_sale',
            'view_reports', 'export_reports',
        ]);
        
        // Staff - limited permissions within their tenant
        $staffRole = Role::create(['name' => 'staff', 'tenant_id' => null]);
        $staffRole->givePermissionTo([
            'view_tenant',
            'view_any_user', 'view_user',
            'create_member', 'edit_member', 'view_any_member', 'view_member',
            'view_any_inventory', 'view_inventory',
            'create_sale', 'view_any_sale', 'view_sale',
            'view_reports',
        ]);
        
        // Member - limited permissions within their tenant
        $memberRole = Role::create(['name' => 'member', 'tenant_id' => null]);
        $memberRole->givePermissionTo([
            'view_member',
        ]);
    }
} 