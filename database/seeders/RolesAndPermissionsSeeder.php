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

        // Define all permissions
        $permissions = [
            // Super Admin specific (central admin panel)
            'manage_tenants', // Covers CRUD for tenants
            'access_central_admin_panel',
            'view_global_analytics',

            // Tenant Admin specific
            'access_tenant_admin_panel',
            'manage_club_settings',     // Branding, pricing, inventory rules
            'invite_staff',
            'manage_staff_permissions', // Assign/revoke specific permissions to staff members or staff role
            'view_any_staff', 'create_staff', 'edit_staff', 'delete_staff',

            'view_any_member', 'create_member', 'edit_member', 'delete_member',
            'approve_member_registration',
            'assign_member_fob_id',
            'manage_member_wallet',     // Top-up, view transactions, etc.

            'manage_inventory',         // Full CRUD on inventory items
            'view_inventory_levels',
            'perform_stock_checks',     // Record check-in/out
            'track_inventory_discrepancies',

            'access_pos_system',
            'generate_tenant_reports',
            'view_tenant_sales_logs',
            'view_staff_activity_logs',

            // Staff specific (permissions an Admin might grant to Staff role)
            // 'access_pos_system' (already listed, essential for staff)
            // 'perform_stock_checks' (already listed)
            // 'view_inventory_levels' (already listed)
            // 'create_member_via_pos' (more specific than general create_member)
            // 'assign_member_fob_id_via_pos'
            // 'process_member_payment_pos' (part of manage_member_wallet, but specific to POS)
            'view_own_sales_logs',

            // Member specific
            'access_member_portal',
            'view_own_profile',
            'view_own_wallet_balance',
            'view_own_purchase_history',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Define Roles
        // Super Admin Role - has tenant_id = null implicitly by not setting it.
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        // Tenant specific roles - these are globally defined role names.
        // Actual assignment to users makes them tenant-specific in practice.
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        // Assign permissions to roles
        // Super Admin gets all permissions
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Role Permissions
        $adminRole->syncPermissions([
            'access_tenant_admin_panel',
            'manage_club_settings',
            'invite_staff',
            'manage_staff_permissions',
            'view_any_staff', 'create_staff', 'edit_staff', 'delete_staff',
            'view_any_member', 'create_member', 'edit_member', 'delete_member',
            'approve_member_registration',
            'assign_member_fob_id',
            'manage_member_wallet',
            'manage_inventory',
            'view_inventory_levels',
            'perform_stock_checks',
            'track_inventory_discrepancies',
            'access_pos_system',
            'generate_tenant_reports',
            'view_tenant_sales_logs',
            'view_staff_activity_logs',
        ]);

        // Staff Role Permissions (sane defaults, Admin can adjust using 'manage_staff_permissions')
        $staffRole->syncPermissions([
            'access_tenant_admin_panel', // Access to necessary parts of the panel
            'access_pos_system',
            'perform_stock_checks',
            'view_inventory_levels',
            // Permissions an Admin might commonly grant:
            'create_member', // If staff are allowed to register members directly
            'assign_member_fob_id', // If staff register members
            'manage_member_wallet', // For POS transactions, should be scoped in code
            'view_own_sales_logs',
            'view_any_member', // To search and select members in POS
        ]);

        // Member Role Permissions
        $memberRole->syncPermissions([
            'access_member_portal',
            'view_own_profile',
            'view_own_wallet_balance',
            'view_own_purchase_history',
        ]);
    }
} 