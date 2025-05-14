<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Wallet;

class CreateUserWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user-wallets {--tenant=} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create wallets for all existing users that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantOption = $this->option('tenant');
        $allOption = $this->option('all');
        $forceOption = $this->option('force');
        
        if (!$tenantOption && !$allOption) {
            $this->error('Please specify either a tenant ID/slug with --tenant or use --all to process all tenants');
            return 1;
        }
        
        if ($tenantOption) {
            // Find tenant by ID or slug
            $tenant = is_numeric($tenantOption) 
                ? Tenant::find($tenantOption) 
                : Tenant::where('slug', $tenantOption)->first();
                
            if (!$tenant) {
                $this->error("Tenant not found with ID/slug: {$tenantOption}");
                return 1;
            }
            
            $this->processTenant($tenant, $forceOption);
        } else {
            // Process all tenants
            $tenants = Tenant::all();
            $this->info("Processing wallets for {$tenants->count()} tenants...");
            
            foreach ($tenants as $tenant) {
                $this->processTenant($tenant, $forceOption);
            }
        }
        
        $this->info('User wallet creation complete!');
        return 0;
    }
    
    /**
     * Process a single tenant
     */
    private function processTenant(Tenant $tenant, bool $force = false)
    {
        if (!$tenant->enable_wallet && !$force) {
            $this->warn("Skipping tenant '{$tenant->name}' because wallet feature is disabled. Use --force to override.");
            return;
        }
        
        $this->info("Processing tenant: {$tenant->name}");
        
        // Get all users for this tenant
        $users = $tenant->users()->get();
        $this->info("Found {$users->count()} users");
        
        $created = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            $walletExists = Wallet::where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->exists();
                
            if (!$walletExists) {
                Wallet::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'balance' => 0,
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }
        
        $this->info("Created {$created} wallets, skipped {$skipped} existing wallets");
    }
} 