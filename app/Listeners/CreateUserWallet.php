<?php

namespace App\Listeners;

use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Filament\Facades\Filament;

class CreateUserWallet implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        
        // Get the tenant from the user's tenants
        $tenant = $user->tenants()->first();
        
        // Skip if no tenant or wallet feature is disabled
        if (!$tenant || !$tenant->enable_wallet) {
            return;
        }
        
        // Check if the user already has a wallet for this tenant
        $walletExists = Wallet::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->exists();
            
        // If wallet doesn't exist, create it
        if (!$walletExists) {
            Wallet::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'balance' => 0,
            ]);
        }
    }
} 