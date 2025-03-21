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
        
        // Create super admin user
        $superAdmin = User::factory()->create([
          'name' => 'Dylan Moore',
          'email' => 'dylan@getpod.app',
          'password' => Hash::make('QpnUWhF$%!E884$5LJFW'),
          'email_verified_at' => now(),
          'remember_token' => Str::random(10),
        ]);
        
        // Assign super admin role (global, not tenant-specific)
        $superAdmin->assignRole('super_admin');
        
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Example Organization',
            'slug' => 'example-organization',
        ]);
        
        // Create admin user
        $admin = User::factory()->create([
          'name' => 'Admin User',
          'email' => 'admin@manage.green',
          'password' => Hash::make('QpnUWhF$%!E884$5LJFW'),
          'email_verified_at' => now(),
          'remember_token' => Str::random(10),
        ]);
        
        // Create a staff user
        $staff = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // Create a member user
        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // Associate users with the tenant
        $tenant->users()->attach([$admin->id, $staff->id, $member->id]);
        
        // Also allow super admin to access all tenants
        $tenant->users()->attach($superAdmin->id);
        
        // Assign tenant-specific roles
        $admin->assignRole('admin', $tenant);
        $staff->assignRole('staff', $tenant);
        $member->assignRole('member', $tenant);
    }
}
