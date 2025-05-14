<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\InventoryTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        $tenantId = tenant()->id;
        
        // Total products
        $totalProducts = Product::where('tenant_id', $tenantId)->count();
        
        // Active products
        $activeProducts = Product::where('tenant_id', $tenantId)
            ->where('active', true)
            ->count();
            
        // Low stock products
        $lowStockProducts = Product::where('tenant_id', $tenantId)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->count();
            
        // Total inventory value
        $totalInventoryValue = Product::where('tenant_id', $tenantId)
            ->select(DB::raw('SUM(current_stock * price) as total_value'))
            ->first()
            ->total_value ?? 0;
            
        // Today's inventory transactions
        $todayTransactions = InventoryTransaction::where('tenant_id', $tenantId)
            ->whereDate('created_at', now())
            ->count();
            
        // Products with most transactions this week
        $mostPopularProduct = InventoryTransaction::where('tenant_id', $tenantId)
            ->where('type', 'sale')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->select('product_id', DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('product_id')
            ->orderBy('transaction_count', 'desc')
            ->with('product:id,name')
            ->first();
            
        $popularProductText = $mostPopularProduct 
            ? $mostPopularProduct->product->name . ' (' . $mostPopularProduct->transaction_count . ' sales)'
            : 'No sales this week';

        return [
            Stat::make('Total Products', $totalProducts)
                ->description($activeProducts . ' active products')
                ->color('primary'),
                
            Stat::make('Low Stock Products', $lowStockProducts)
                ->description('Products below minimum stock level')
                ->color($lowStockProducts > 0 ? 'danger' : 'success'),
                
            Stat::make('Inventory Value', '€' . number_format($totalInventoryValue, 2))
                ->description('Total value of current stock')
                ->color('success'),
                
            Stat::make('Today\'s Transactions', $todayTransactions)
                ->description('Inventory movements today')
                ->color('info'),
                
            Stat::make('Most Popular Product', '')
                ->description($popularProductText)
                ->color('warning'),
        ];
    }
} 