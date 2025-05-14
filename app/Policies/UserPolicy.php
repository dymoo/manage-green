<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Filament\Facades\Filament;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only allow access if within a tenant context
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Allow admin or staff
        return $user->hasAnyRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Check if the model being viewed belongs to the same tenant
        if ($model->tenant_id !== $tenant->id) {
            return false;
        }
        // Allow admin or staff to view users in their tenant
        return $user->hasAnyRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Allow admin or staff
        return $user->hasAnyRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Check if the model being updated belongs to the same tenant
        if ($model->tenant_id !== $tenant->id) {
            return false;
        }
        // Allow admin or staff to update users in their tenant
        return $user->hasAnyRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
         if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Admins can delete any user within the tenant (except themselves?)
        return $user->hasRole('admin', $tenant) && $model->tenant_id === $tenant->id && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        return $user->hasRole('admin', $tenant) && $model->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        return $user->hasRole('admin', $tenant) && $model->tenant_id === $tenant->id && $user->id !== $model->id;
    }
}
