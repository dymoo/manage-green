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
        $permissions = [
            // Tenant permissions
            'create_tenant', 'edit_tenant', 'delete_tenant', 'view_any_tenant', 'view_tenant',
            // User permissions
            'create_user', 'edit_user', 'delete_user', 'view_any_user', 'view_user',
            // Member permissions
            'create_member', 'edit_member', 'delete_member', 'view_any_member', 'view_member',
            // Inventory permissions
            'create_inventory', 'edit_inventory', 'delete_inventory', 'view_any_inventory', 'view_inventory',
            // Sales permissions
            'create_sale', 'view_any_sale', 'view_sale',
            // Report permissions
            'view_reports', 'export_reports',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        
        // Check if roles exist before creating them
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'tenant_id' => null]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'tenant_id' => null]);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'tenant_id' => null]);
        $memberRole = Role::firstOrCreate(['name' => 'member', 'tenant_id' => null]);
        
        // Sync permissions for each role
        $superAdminRole->syncPermissions(Permission::all());
        
        $adminRole->syncPermissions([
            'edit_tenant', 'view_tenant',
            'create_user', 'edit_user', 'delete_user', 'view_any_user', 'view_user',
            'create_member', 'edit_member', 'delete_member', 'view_any_member', 'view_member',
            'create_inventory', 'edit_inventory', 'delete_inventory', 'view_any_inventory', 'view_inventory',
            'create_sale', 'view_any_sale', 'view_sale',
            'view_reports', 'export_reports',
        ]);
        
        $staffRole->syncPermissions([
            'view_tenant',
            'view_any_user', 'view_user',
            'create_member', 'edit_member', 'view_any_member', 'view_member',
            'view_any_inventory', 'view_inventory',
            'create_sale', 'view_any_sale', 'view_sale',
            'view_reports',
        ]);
        
        $memberRole->syncPermissions([
            'view_member',
        ]);
    }
} 