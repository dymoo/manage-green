<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;
use Filament\Facades\Filament;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Automatically create a wallet for the user within the current tenant context
        if ($tenant = Filament::getTenant() ?? $user->tenant) { // Try Filament context first, then user's tenant relationship
             if ($tenant && $user->tenant_id) { // Ensure user has a tenant_id
                Wallet::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id, // Use tenant ID from context or relationship
                    'balance' => 0.00, // Initial balance
                ]);
             }
        } 
        // If no tenant context found, maybe log an error or handle differently?
        // Consider scenarios like global user creation outside tenant scope.
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
