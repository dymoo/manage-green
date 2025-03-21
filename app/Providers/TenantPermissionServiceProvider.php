<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantPermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add tenant ID scope to role queries when in tenant context
        Role::addGlobalScope('tenant', function (Builder $query) {
            $tenant = Filament::getTenant();
            
            if ($tenant) {
                // Get the table name
                $table = $query->getModel()->getTable();
                
                $query->where(function (Builder $subQuery) use ($tenant, $table) {
                    $subQuery->where("{$table}.tenant_id", $tenant->id)
                             ->orWhereNull("{$table}.tenant_id"); // Include global roles
                });
            }
        });
        
        // Modify the Spatie Permission middleware to check tenant-specific roles
        $this->app->extend('permission.role', function ($service, $app) {
            return new class {
                public function handle($request, $next, $role) {
                    $roles = is_array($role) ? $role : explode('|', $role);
                    
                    if (!$request->user()) {
                        abort(403);
                    }
                    
                    $tenant = Filament::getTenant();
                    
                    if ($tenant && !$request->user()->hasRole($roles, $tenant)) {
                        abort(403);
                    } elseif (!$tenant && !$request->user()->hasRole($roles)) {
                        abort(403);
                    }
                    
                    return $next($request);
                }
            };
        });
    }
} 