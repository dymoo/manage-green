<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@manage.green',
            'password' => Hash::make('password'), // Change this in production
        ]);

        // Assign the super_admin role
        $superAdmin->assignRole('super_admin');
    }
}
