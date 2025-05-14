<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->record;
        /** @var User $user */
        $user = Auth::user();

        // Attach the creator to the tenant
        $tenant->users()->attach($user);

        // Define the standard roles for this tenant
        $roles = ['Admin', 'Staff', 'User'];
        $guardName = 'web';

        $adminRole = null; // Initialize adminRole variable

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'tenant_id' => $tenant->id, // Scope the role to this tenant
                'guard_name' => $guardName,
            ]);

            // Keep track of the Admin role specifically
            if ($roleName === 'Admin') {
                $adminRole = $role;
            }
        }

        // Assign the Admin role to the creator
        if ($adminRole) {
            // Ensure the assignRole method respects the tenant context
            // (The custom HasTenantPermissions trait should handle this)
            $user->assignRole($adminRole);
        } else {
            // Handle error: Admin role couldn't be found or created
            // You might want to log this or throw an exception
            report(new \Exception("Could not find or create Admin role for tenant {$tenant->id}"));
        }
    }
} 