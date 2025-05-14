<?php

namespace App\Policies;

use App\Models\StockCheck;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Filament\Facades\Filament;

class StockCheckPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Admins and Staff can view the list of stock checks
        return $user->hasRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StockCheck $stockCheck): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Admins and Staff can view a stock check if it belongs to the current tenant
        return $user->hasRole(['admin', 'staff'], $tenant) && $stockCheck->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can create models (check-in).
     */
    public function create(User $user): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Admins and Staff can initiate a stock check-in
        return $user->hasRole(['admin', 'staff'], $tenant);
    }

    /**
     * Determine whether the user can update the model (check-out).
     */
    public function update(User $user, StockCheck $stockCheck): bool
    {
         if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Check belongs to the current tenant AND (user is admin OR user is the one who checked in)
        return $stockCheck->tenant_id === $tenant->id &&
               ($user->hasRole('admin', $tenant) || $user->id === $stockCheck->staff_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockCheck $stockCheck): bool
    {
         if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Only admins can delete stock checks for now
        return $user->hasRole('admin', $tenant) && $stockCheck->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockCheck $stockCheck): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Only admins can restore
        return $user->hasRole('admin', $tenant) && $stockCheck->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockCheck $stockCheck): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }
        // Only admins can force delete
        return $user->hasRole('admin', $tenant) && $stockCheck->tenant_id === $tenant->id;
    }
}
