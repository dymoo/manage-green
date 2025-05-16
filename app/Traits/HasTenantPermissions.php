<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;

trait HasTenantPermissions
{
    use HasRoles {
        HasRoles::assignRole as spatieAssignRole;
        HasRoles::hasRole as spatieHasRole;
        HasRoles::hasAnyRole as spatieHasAnyRole;
        HasRoles::hasAllRoles as spatieHasAllRoles;
        HasRoles::removeRole as spatieRemoveRole;
        HasRoles::getRoleNames as spatieGetRoleNames;
    }

    /**
     * Assign the given role to the model in the specified tenant.
     *
     * @param array|string|int|\Spatie\Permission\Contracts\Role ...$roles
     * @param \App\Models\Tenant|int|null $tenant
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $tenant = null;
        
        // If the last argument is a Tenant or tenant ID, pop it off
        $lastArg = last($roles);
        if ($lastArg instanceof Tenant || is_numeric($lastArg)) {
            $tenant = array_pop($roles);
            if (is_numeric($tenant)) {
                $tenant = Tenant::findOrFail($tenant);
            }
        }

        // Get tenant ID for the current context if tenant wasn't specified
        if (!$tenant && app()->has('currentTenant')) {
            $tenant = app('currentTenant');
        }
        
        $processedRoles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }
                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role !== false;
            })
            ->unique('id');

        if ($processedRoles->isEmpty()) {
            return $this;
        }

        // If no tenant specified and no current tenant, handle global role assignment
        if (!$tenant) {
            // Use direct DB insert for global roles to bypass potential attach issues
            $modelType = get_class($this);
            $modelId = $this->getKey();
            $pivotTable = config('permission.table_names.model_has_roles');
            
            $insertData = $processedRoles->map(function ($role) use ($modelType, $modelId) {
                return [
                    'role_id' => $role->id,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'tenant_id' => null, // Use tenant_id column instead of team_id
                ];
            })->all();
            
            if (!empty($insertData)) {
                DB::table($pivotTable)->insert($insertData);
            }
            
            // We might need to call $this->forgetCachedPermissions(); here if Spatie's cache doesn't pick this up.
            return $this;
        }
        
        // Handle tenant-specific role assignment
        $tenantId = $tenant->id;
        $attachData = $processedRoles->mapWithKeys(function ($role) use ($tenantId) {
            return [$role->id => ['tenant_id' => $tenantId]]; // Use tenant_id column instead of team_id
        })->all();
        $this->roles()->attach($attachData);
        // We might need to call $this->forgetCachedPermissions(); here.
        
        return $this;
    }

    /**
     * Remove the given role from the model in the specified tenant.
     *
     * @param array|string|int|\Spatie\Permission\Contracts\Role ...$roles
     * @param \App\Models\Tenant|int|null $tenant
     * @return $this
     */
    public function removeRole(...$roles)
    {
        $tenant = null;
        
        // If the last argument is a Tenant or tenant ID, pop it off
        $lastArg = last($roles);
        if ($lastArg instanceof Tenant || is_numeric($lastArg)) {
            $tenant = array_pop($roles);
            if (is_numeric($tenant)) {
                $tenant = Tenant::findOrFail($tenant);
            }
        }

        // Get tenant ID for the current context if tenant wasn't specified
        if (!$tenant && app()->has('currentTenant')) {
            $tenant = app('currentTenant');
        }
        
        // If no tenant specified and no current tenant, use global role removal
        if (!$tenant) {
            return $this->spatieRemoveRole(...$roles);
        }
        
        $tenantId = $tenant->id;
        
        collect($roles)
            ->flatten()
            ->map(function ($role) use ($tenantId) {
                $role = $this->getStoredRole($role);
                
                $this->roles()->wherePivot('tenant_id', $tenantId)->detach($role);
                
                return $role;
            });

        return $this;
    }

    /**
     * Determine if the model has any of the given roles in the specified tenant.
     *
     * @param array|string|int|\Spatie\Permission\Contracts\Role ...$roles
     * @param \App\Models\Tenant|int|null $tenant
     * @return bool
     */
    public function hasRole(...$roles): bool
    {
        $tenant = null;
        
        // If the last argument is a Tenant or tenant ID, pop it off
        $lastArg = last($roles);
        if ($lastArg instanceof Tenant || is_numeric($lastArg)) {
            $tenant = array_pop($roles);
            if (is_numeric($tenant)) {
                $tenant = Tenant::findOrFail($tenant);
            }
        }

        // Get tenant ID for the current context if tenant wasn't specified
        if (!$tenant && app()->has('currentTenant')) {
            $tenant = app('currentTenant');
        }
        
        // If the user is a super_admin, they have all roles
        if ($this->spatieHasRole('super_admin')) {
            return true;
        }
        
        // If no tenant specified and no current tenant, use global role check
        if (!$tenant) {
            return $this->spatieHasRole(...$roles);
        }
        
        $tenantId = $tenant->id;
        
        if (empty($roles)) {
            return false;
        }

        $roles = collect($roles)->flatten();

        // Get the table names from config
        $roleClass = get_class(app(config('permission.models.role')));
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = $roleClass::where([
                    'name' => $role,
                    'tenant_id' => $tenantId
                ])->first();
                if (!$role) {
                    continue;
                }
            } elseif (is_numeric($role)) {
                $role = $roleClass::where([
                    'id' => $role,
                    'tenant_id' => $tenantId
                ])->first();
                if (!$role) {
                    continue;
                }
            }
            
            $roleIds[] = $role->id;
        }
        
        if (empty($roleIds)) {
            return false;
        }
        
        // Use direct query with proper table aliases
        $modelType = get_class($this);
        $modelId = $this->getKey();
        
        $pivotTable = config('permission.table_names.model_has_roles');
        
        $count = DB::table($pivotTable)
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('tenant_id', $tenantId) // Use tenant_id directly
            ->whereIn('role_id', $roleIds)
            ->count();
            
        return $count > 0;
    }

    /**
     * Determine if the model has any of the given roles in the specified tenant.
     *
     * @param array|\Spatie\Permission\Contracts\Role|string ...$roles
     * @param \App\Models\Tenant|int|null $tenant
     * @return bool
     */
    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole(...$roles);
    }

    /**
     * Determine if the model has all of the given roles in the specified tenant.
     *
     * @param array|\Spatie\Permission\Contracts\Role|string ...$roles
     * @param \App\Models\Tenant|int|null $tenant
     * @return bool
     */
    public function hasAllRoles(...$roles): bool
    {
        $tenant = null;
        
        // If the last argument is a Tenant or tenant ID, pop it off
        $lastArg = last($roles);
        if ($lastArg instanceof Tenant || is_numeric($lastArg)) {
            $tenant = array_pop($roles);
            if (is_numeric($tenant)) {
                $tenant = Tenant::findOrFail($tenant);
            }
        }

        // Get tenant ID for the current context if tenant wasn't specified
        if (!$tenant && app()->has('currentTenant')) {
            $tenant = app('currentTenant');
        }
        
        // If the user is a super_admin, they have all roles
        if ($this->spatieHasRole('super_admin')) {
            return true;
        }
        
        // If no tenant specified and no current tenant, use global role check
        if (!$tenant) {
            return $this->spatieHasAllRoles(...$roles);
        }
        
        $tenantId = $tenant->id;
        
        if (empty($roles)) {
            return false;
        }

        $roles = collect($roles)->flatten();

        // Get the table names from config
        $roleClass = get_class(app(config('permission.models.role')));
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = $roleClass::where('name', $role)->first();
                if (!$role) {
                    return false; // Role doesn't exist, so user can't have it
                }
            } elseif (is_numeric($role)) {
                $role = $roleClass::find($role);
                if (!$role) {
                    return false; // Role doesn't exist, so user can't have it
                }
            }
            
            $roleIds[] = $role->id;
        }
        
        if (empty($roleIds)) {
            return false;
        }
        
        // Use direct query with proper table aliases
        $modelType = get_class($this);
        $modelId = $this->getKey();
        
        $pivotTable = config('permission.table_names.model_has_roles');
        
        $matchedCount = \DB::table($pivotTable)
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('tenant_id', $tenantId)
            ->whereIn('role_id', $roleIds)
            ->count();
            
        // User has all roles if the number of matched roles equals the number of requested roles
        return count($roleIds) === $matchedCount;
    }

    /**
     * Get the role names for the model in the specified tenant.
     *
     * @param \App\Models\Tenant|int|null $tenant
     * @return Collection
     */
    public function getRoleNames($tenant = null): Collection
    {
        // Get tenant ID for the current context if tenant wasn't specified
        if (!$tenant && app()->has('currentTenant')) {
            $tenant = app('currentTenant');
        }

        // If no tenant specified and no current tenant, get all role names
        if (!$tenant) {
            return $this->spatieGetRoleNames();
        }
        
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        
        // Use direct query with proper table aliases
        $modelType = get_class($this);
        $modelId = $this->getKey();
        
        $pivotTable = config('permission.table_names.model_has_roles');
        $roleTable = config('permission.table_names.roles');
        
        return collect(\DB::table($pivotTable)
            ->join($roleTable, "$pivotTable.role_id", '=', "$roleTable.id")
            ->where("$pivotTable.model_type", $modelType)
            ->where("$pivotTable.model_id", $modelId)
            ->where("$pivotTable.tenant_id", $tenantId)
            ->pluck("$roleTable.name"));
    }

    /**
     * Override the roles relation to handle tenant-specific roles.
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            $pivotTable,
            config('permission.column_names.model_morph_key'),
            'role_id'
        );

        if (app()->has('currentTenant')) {
            $tenantId = app('currentTenant')->id;
            $relation->wherePivot('tenant_id', $tenantId);
        }

        return $relation;
    }
} 