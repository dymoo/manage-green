<?php

namespace App\Filament\Resources\InventoryTransactionResource\Widgets;

use App\Models\InventoryTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DailyDiscrepancyReport extends BaseWidget
{
    protected function getStats(): array
    {
        $tenantId = tenant()->id;
        
        // Get today's transactions of type 'adjustment'
        $today = now()->startOfDay();
        
        // Count the number of adjustments today
        $adjustmentsCount = InventoryTransaction::where('tenant_id', $tenantId)
            ->where('type', 'adjustment')
            ->whereDate('created_at', $today)
            ->count();
            
        // Calculate total discrepancy in grams
        $totalDiscrepancy = InventoryTransaction::where('tenant_id', $tenantId)
            ->where('type', 'adjustment')
            ->whereDate('created_at', $today)
            ->sum('quantity');
            
        // Get products with the largest discrepancies
        $largestDiscrepancies = InventoryTransaction::where('tenant_id', $tenantId)
            ->where('type', 'adjustment')
            ->whereDate('created_at', $today)
            ->select('product_id', DB::raw('SUM(quantity) as total_discrepancy'))
            ->groupBy('product_id')
            ->orderByRaw('ABS(SUM(quantity)) DESC')
            ->limit(3)
            ->with('product:id,name')
            ->get();
            
        $discrepancyDetails = '';
        foreach ($largestDiscrepancies as $index => $discrepancy) {
            $sign = $discrepancy->total_discrepancy > 0 ? '+' : '';
            $discrepancyDetails .= ($index + 1) . '. ' . $discrepancy->product->name . ': ' . 
                $sign . number_format($discrepancy->total_discrepancy, 3) . 'g' . PHP_EOL;
        }
        
        if (empty($discrepancyDetails)) {
            $discrepancyDetails = 'No discrepancies recorded today';
        }
        
        // Find staff with most adjustments
        $staffWithMostAdjustments = InventoryTransaction::where('tenant_id', $tenantId)
            ->where('type', 'adjustment')
            ->whereDate('created_at', $today)
            ->select('staff_id', DB::raw('COUNT(*) as adjustment_count'))
            ->groupBy('staff_id')
            ->orderBy('adjustment_count', 'desc')
            ->with('staff:id,name')
            ->first();
            
        $staffDetails = '';
        if ($staffWithMostAdjustments) {
            $staffDetails = $staffWithMostAdjustments->staff->name . 
                ' (' . $staffWithMostAdjustments->adjustment_count . ' adjustments)';
        } else {
            $staffDetails = 'No adjustments recorded today';
        }

        return [
            Stat::make('Adjustments Today', $adjustmentsCount)
                ->description('Number of inventory adjustments')
                ->color('primary'),
                
            Stat::make('Total Discrepancy', number_format($totalDiscrepancy, 3) . 'g')
                ->description('Net weight difference')
                ->color($totalDiscrepancy >= 0 ? 'success' : 'danger'),
                
            Stat::make('Largest Discrepancies', '')
                ->description($discrepancyDetails)
                ->color('warning'),
                
            Stat::make('Staff Activity', '')
                ->description($staffDetails)
                ->color('info'),
        ];
    }
} 