<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default manage-green tenant if it doesn't exist
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'manage-green'],
            [
                'name' => 'Manage Green',
                'slug' => 'manage-green',
            ]
        );
        
        // Find all users with super_admin role
        // This assumes you have a role system where super admins can be identified
        $superAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->get();
        
        // If no super admins found, use all users for now (you can adjust this logic)
        if ($superAdmins->isEmpty()) {
            $superAdmins = User::all();
        }
        
        // Associate all super admin users with this tenant
        foreach ($superAdmins as $admin) {
            // Check if the relationship already exists to avoid duplication
            if (!$admin->tenants()->where('tenant_id', $tenant->id)->exists()) {
                $admin->tenants()->attach($tenant->id);
                
                // If the user doesn't have the admin role for this tenant, assign it
                if (!$admin->hasRole('admin', $tenant)) {
                    $admin->assignRole('admin', $tenant);
                }
            }
        }
        
        $this->command->info('Default "Manage Green" tenant created and associated with super admin users.');
    }
} 