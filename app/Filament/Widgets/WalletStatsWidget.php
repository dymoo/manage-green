<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

class WalletStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15m';
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    public static function canView(): bool
    {
        $tenant = Filament::getTenant();
        return $tenant && $tenant->enable_wallet;
    }

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return [];
        }
        
        // Get total members with wallets
        $walletCount = Wallet::where('tenant_id', $tenant->id)->count();
        
        // Get total wallet balance across all members
        $totalBalance = Wallet::where('tenant_id', $tenant->id)
            ->sum('balance');
        
        // Get today's deposits
        $todayDeposits = WalletTransaction::whereHas('wallet', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->where('type', 'deposit')
            ->whereDate('created_at', today())
            ->sum('amount');
        
        return [
            Stat::make('Member Wallets', $walletCount)
                ->description('Total member wallets')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
            
            Stat::make('Total Balance', ($tenant->currency ?? '€') . ' ' . number_format($totalBalance, 2))
                ->description('Combined wallet balance')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Today\'s Deposits', ($tenant->currency ?? '€') . ' ' . number_format($todayDeposits, 2))
                ->description('Funds added today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
} 