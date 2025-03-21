<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the roles and permissions seeder first
        $this->call(RolesAndPermissionsSeeder::class);
        
        // Skip the SuperAdminSeeder since we're creating users here
        // $this->call(SuperAdminSeeder::class);
        
        // Create super admin user if it doesn't exist
        $superAdmin = User::firstOrCreate(
            ['email' => 'dylan@getpod.app'],
            [
                'name' => 'Dylan Moore',
                'password' => Hash::make('QpnUWhF$%!E884$5LJFW'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
        
        // Assign super admin role (global, not tenant-specific)
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }
        
        // Create or find the tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'example-organization'],
            [
                'name' => 'Example Organization',
                'slug' => 'example-organization',
            ]
        );
        
        // Create admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@manage.green'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('QpnUWhF$%!E884$5LJFW'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
        
        // Create a staff user if it doesn't exist
        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
        
        // Create a member user if it doesn't exist
        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
        
        // Associate users with the tenant if they aren't already
        if (!$tenant->users()->where('user_id', $admin->id)->exists()) {
            $tenant->users()->attach($admin->id);
        }
        
        if (!$tenant->users()->where('user_id', $staff->id)->exists()) {
            $tenant->users()->attach($staff->id);
        }
        
        if (!$tenant->users()->where('user_id', $member->id)->exists()) {
            $tenant->users()->attach($member->id);
        }
        
        // Also allow super admin to access all tenants
        if (!$tenant->users()->where('user_id', $superAdmin->id)->exists()) {
            $tenant->users()->attach($superAdmin->id);
        }
        
        // Assign tenant-specific roles if they don't have them already
        if (!$admin->hasRole('admin', $tenant)) {
            $admin->assignRole('admin', $tenant);
        }
        
        if (!$staff->hasRole('staff', $tenant)) {
            $staff->assignRole('staff', $tenant);
        }
        
        if (!$member->hasRole('member', $tenant)) {
            $member->assignRole('member', $tenant);
        }
        
        // Create the default "manage-green" tenant for super admins
        $this->call(DefaultTenantSeeder::class);
    }
}
